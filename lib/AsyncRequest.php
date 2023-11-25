<?php

/**
 * Class to handle asynchronous requests
 */
class AsyncRequestHandler
{
    /**
     * Create and return a socket connection
     *
     * @param array $parsedUrl The parsed URL components
     * @return resource The socket connection
     * @throws \Exception If there is a connection error
     */
    private function createSocket($parsedUrl)
    {
        $scheme = $parsedUrl['scheme'];
        $host = $parsedUrl['host'];
        $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : 80;
        $path = $parsedUrl['path'];

        $socket = @stream_socket_client("$scheme://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            throw new \Exception("$errstr ($errno)");
        }

        // Set non-blocking mode
        stream_set_blocking($socket, 0);

        return $socket;
    }

    /**
     * Send the HTTP request to the server
     *
     * @param resource $socket The socket connection
     * @param string $method The HTTP method
     * @param array $parsedUrl The parsed URL components
     * @param object|array $payload The data to send
     */
    private function sendRequest($socket, $method, $parsedUrl, $payload)
    {
        $content = json_encode($payload);

        $request = "$method {$parsedUrl['path']} HTTP/1.1\r\n";
        $request .= "Host: {$parsedUrl['host']}:{$parsedUrl['port']}\r\n";
        $request .= "Content-Type: application/json\r\n";
        $request .= "Content-Length: " . strlen($content) . "\r\n";
        $request .= "Connection: close\r\n\r\n";
        $request .= $content;

        @fwrite($socket, $request);
    }

    /**
     * Wait for the response from the server
     *
     * @param resource $socket The socket connection
     * @return string The server's response
     * @throws \Exception If there is an error during the request or a timeout occurs
     */
    private function waitForResponse($socket)
    {
        $streams = [$socket];
        $write = $except = null;
        $selectResult = @stream_select($streams, $write, $except, 5); // Wait for data for up to 5 seconds

        if ($selectResult === false) {
            throw new \Exception("stream_select error");
        } elseif ($selectResult === 0) {
            throw new \Exception("Request timed out");
        }

        return stream_get_contents($socket);
    }

    /**
     * Parse the HTTP response into header and body
     *
     * @param string $response The server's response
     * @return array The header and body of the response
     */
    private function parseResponse($response)
    {
        return explode("\r\n\r\n", $response, 2);
    }

    /**
     * Parse the HTTP headers into an associative array
     *
     * @param string $header The raw header string
     * @return array The parsed headers
     */
    private function parseHeaders($header)
    {
        $headers = [];
        $headerLines = explode("\r\n", $header);

        foreach ($headerLines as $line) {
            $parts = explode(": ", $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower($parts[0])] = $parts[1];
            }
        }

        return $headers;
    }

    /**
     * Make an async request
     *
     * @param string $method The HTTP method
     * @param string $url The request URL address
     * @param object|array $payload The data to send
     * @param callable $callback A callback where the request's response is sent
     * @throws \Exception If there are any errors during the request
     */
    public function makeAsyncRequest($method, $url, $payload, $callback)
    {
        try {
            $parsedUrl = parse_url($url);

            if ($parsedUrl === false) {
                throw new \Exception("Invalid URL: $url");
            }

            $socket = $this->createSocket($parsedUrl);

            $this->sendRequest($socket, $method, $parsedUrl, $payload);
            $response = $this->waitForResponse($socket);

            if ($response) {
                list($header, $body) = $this->parseResponse($response);

                $headers = $this->parseHeaders($header);
            } else {
                $body = false;
                $headers = [];
            }

            call_user_func($callback, $body, $headers);
        } catch (\Exception $e) {
            call_user_func($callback, $e, []);
        } finally {
            if (isset($socket)) {
                fclose($socket);
            }
        }
    }
}
