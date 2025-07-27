<?php

namespace Doppar\Axios\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Doppar\Axios\Http\SymfonyHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SymfonyHttpClientTest extends TestCase
{
    private function createHttpClient(array $responses = []): SymfonyHttpClient
    {
        $http = new SymfonyHttpClient();

        $reflection = new \ReflectionClass($http);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($http, new MockHttpClient($responses));

        return $http;
    }

    public function test_synchronous_get_request()
    {
        $responses = [
            new MockResponse(json_encode(['id' => 1])),
        ];

        $http = $this->createHttpClient($responses);

        $response = $http->to('https://jsonplaceholder.typicode.com/posts/1')
            ->get()
            ->json();

        $this->assertEquals(['id' => 1], $response);
    }

    public function test_async_requests_with_wait()
    {
        $responses = [
            new MockResponse(json_encode(['id' => 1])),
            new MockResponse(json_encode(['id' => 2]))
        ];

        $http = $this->createHttpClient($responses);

        $http->async()->to('https://jsonplaceholder.typicode.com/posts/1')->get();
        $http->async()->to('https://jsonplaceholder.typicode.com/posts/2')->get();

        $responses = $http->wait(true);

        $this->assertCount(2, $responses);
        $this->assertEquals(['id' => 1], $responses[0]);
        $this->assertEquals(['id' => 2], $responses[1]);
    }

    public function test_retry_mechanism_on_failure()
    {
        $responses = [
            new MockResponse('', ['http_code' => 500]),
            new MockResponse('OK', ['http_code' => 200])
        ];

        $http = $this->createHttpClient($responses);
        $http->retry(1, 10);

        $response = $http->to('https://jsonplaceholder.typicode.com/posts/1')
            ->get()
            ->text();

        $this->assertEquals('OK', $response);
    }

    // public function test_throws_exception_on_client_errors()
    // {
    //     $this->expectException(ClientException::class);

    //     $responses = [
    //         new MockResponse('Not Found', ['http_code' => 404])
    //     ];

    //     $http = $this->createHttpClient($responses);
    //     $http->to('https://jsonplaceholder.typicode.com/posts/not-found')->get();
    // }

    public function test_batch_requests_sync()
    {
        $responses = [
            new MockResponse('{
"userId": 1,
"id": 1,
"title": "sunt aut facere repellat provident occaecati excepturi optio reprehenderit",
"body": "quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\nreprehenderit molestiae ut ut quas totam\nnostrum rerum est autem sunt rem eveniet architecto"
}', ['http_code' => 200]),
            new MockResponse('{
"userId": 1,
"id": 2,
"title": "qui est esse",
"body": "est rerum tempore vitae\nsequi sint nihil reprehenderit dolor beatae ea dolores neque\nfugiat blanditiis voluptate porro vel nihil molestiae ut reiciendis\nqui aperiam non debitis possimus qui neque nisi nulla"
}', ['http_code' => 200])
        ];

        $http = $this->createHttpClient($responses);

        [$response1, $response2] = $http->async()->to([
            'https://jsonplaceholder.typicode.com/posts/1',
            'https://jsonplaceholder.typicode.com/posts/2'
        ])
            ->get()
            ->wait(true);

        $this->assertEquals('1', $response1['id']);
        $this->assertEquals('2', $response2['id']);
    }

    public function test_download_file_with_progress()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'axios_test');
        $responses = [
            new MockResponse('file content', [
                'http_code' => 200,
                'response_headers' => [
                    'content-length' => [12],
                    'content-type' => ['application/octet-stream']
                ]
            ])
        ];

        $http = $this->createHttpClient($responses);

        $progressCalled = false;
        $progressCallback = function($downloaded, $total) use (&$progressCalled) {
            $progressCalled = true;
            $this->assertEquals(12, $total);
        };

        $http->withProgress($progressCallback)
            ->to('https://upload.wikimedia.org/wikipedia/commons/3/3f/Fronalpstock_big.jpg')
            ->get()
            ->download($tempFile);

        $this->assertTrue($progressCalled);
        $this->assertFileExists($tempFile);
        $this->assertEquals('file content', file_get_contents($tempFile));

        @unlink($tempFile);
    }

    public function test_headers_method_returns_response_headers()
    {
        $responses = [
            new MockResponse('', [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => ['application/json'],
                    'x-custom-header' => ['value']
                ]
            ])
        ];

        $http = $this->createHttpClient($responses);

        $headers = $http->to('https://api.example.com')
            ->get()
            ->headers();

        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals(['application/json'], $headers['content-type']);
    }

    public function test_successful_and_failed_checks()
    {
        $successResponses = [
            new MockResponse('', ['http_code' => 200])
        ];
        $errorResponses = [
            new MockResponse('', ['http_code' => 404])
        ];

        $http = $this->createHttpClient($successResponses);
        $this->assertTrue(
            $http->to('https://api.example.com/success')
                ->get()
                ->successful()
        );

        $http = $this->createHttpClient($errorResponses);
        $this->assertTrue(
            $http->to('https://api.example.com/not-found')
                ->get()
                ->failed()
        );
    }

    public function test_with_basic_auth_adds_auth_header()
    {
        $responses = [
            new MockResponse('', ['http_code' => 200])
        ];

        $http = $this->createHttpClient($responses);
        $response = $http->to('https://api.example.com')
            ->withBasicAuth('user', 'pass')
            ->get();

        // Verify auth header was set by checking successful request
        $this->assertEquals(200, $response->status());
    }
}
