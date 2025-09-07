<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\MediaType;
use Kirameki\Testing\TestCase;

final class MediaTypeTest extends TestCase
{
    public function test_constructor_with_type_and_subtype(): void
    {
        $mediaType = new MediaType('text', 'html');

        $this->assertSame('text', $mediaType->type);
        $this->assertSame('html', $mediaType->subtype);
        $this->assertSame([], $mediaType->parameters);
    }

    public function test_constructor_with_parameters(): void
    {
        $parameters = ['charset' => 'utf-8', 'boundary' => 'something'];
        $mediaType = new MediaType('text', 'html', $parameters);

        $this->assertSame('text', $mediaType->type);
        $this->assertSame('html', $mediaType->subtype);
        $this->assertSame($parameters, $mediaType->parameters);
    }

    public function test_constructor_with_empty_parameters(): void
    {
        $mediaType = new MediaType('application', 'json', []);

        $this->assertSame('application', $mediaType->type);
        $this->assertSame('json', $mediaType->subtype);
        $this->assertSame([], $mediaType->parameters);
    }

    public function test_readonly_properties(): void
    {
        $mediaType = new MediaType('image', 'png');

        // Verify properties are accessible
        $this->assertIsString($mediaType->type);
        $this->assertIsString($mediaType->subtype);
        $this->assertIsArray($mediaType->parameters);
    }

    public function test_toString_basic_media_type(): void
    {
        $mediaType = new MediaType('text', 'html');
        $this->assertSame('text/html', $mediaType->toString());
    }

    public function test_toString_with_single_parameter(): void
    {
        $mediaType = new MediaType('text', 'html', ['charset' => 'utf-8']);
        $this->assertSame('text/html;charset=utf-8', $mediaType->toString());
    }

    public function test_toString_with_multiple_parameters(): void
    {
        $mediaType = new MediaType('text', 'html', [
            'charset' => 'utf-8',
            'boundary' => 'something'
        ]);
        $this->assertSame('text/html;charset=utf-8; boundary=something', $mediaType->toString());
    }

    public function test_toString_application_json(): void
    {
        $mediaType = new MediaType('application', 'json');
        $this->assertSame('application/json', $mediaType->toString());
    }

    public function test_toString_multipart_form_data_with_boundary(): void
    {
        $mediaType = new MediaType('multipart', 'form-data', ['boundary' => '----WebKitFormBoundary7MA4YWxkTrZu0gW']);
        $this->assertSame('multipart/form-data;boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW', $mediaType->toString());
    }

    public function test_toString_with_empty_parameters(): void
    {
        $mediaType = new MediaType('image', 'png', []);
        $this->assertSame('image/png', $mediaType->toString());
    }

    public function test___toString_basic_media_type(): void
    {
        $mediaType = new MediaType('text', 'plain');
        $this->assertSame('text/plain', (string) $mediaType);
    }

    public function test___toString_with_parameters(): void
    {
        $mediaType = new MediaType('text', 'plain', ['charset' => 'iso-8859-1']);
        $this->assertSame('text/plain;charset=iso-8859-1', (string) $mediaType);
    }

    public function test___toString_matches_toString(): void
    {
        $mediaType = new MediaType('application', 'xml', [
            'charset' => 'utf-8',
            'version' => '1.0'
        ]);
        $this->assertSame($mediaType->toString(), (string) $mediaType);
    }

    public function test___toString_in_string_context(): void
    {
        $mediaType = new MediaType('video', 'mp4');
        $result = "Content-Type: {$mediaType}";
        $this->assertSame('Content-Type: video/mp4', $result);
    }
}
