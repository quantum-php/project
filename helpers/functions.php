<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\HttpClient\Contracts\CurlAdapterInterface;
use Quantum\HttpClient\Exceptions\HttpClientException;
use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;

/**
 * Gets the url with the selected language
 * @param string $lang
 * @return string
 */
function url_with_lang(string $lang): string
{
    if (!in_array($lang, (array) config()->get('lang.supported'))) {
        $lang = config()->get('lang.default_locale');
    }

    if (trim(route_uri()) == '/') {
        return base_url(true) . '/' . $lang;
    }

    if (preg_match('/' . preg_quote(current_lang(), '/') . '/', route_uri())) {
        return base_url() . preg_replace('/' . preg_quote(current_lang(), '/') . '/', (string) $lang, route_uri(), 1);
    }

    $url = base_url(true);

    $langSegmentIndex = config()->get('lang.url_segment');

    $uri = '';

    if (!empty(route_prefix()) && $langSegmentIndex == 1) {
        $langSegmentIndex += 1;

        $uri = preg_replace('/' . preg_quote(route_prefix(), '/') . '/', '', route_uri(), 1);
    }

    $segments = explode('/', $uri);

    foreach ($segments as $index => $segment) {
        if (!empty($segment)) {
            $url .= '/' . ($index == $langSegmentIndex ? $lang : $segment);
        }
    }

    return $url;
}

/**
 * Saves remote image
 * @param string $imageUrl
 * @param string $userDirectory
 * @param string $imageName
 * @return string
 * @throws ErrorException
 * @throws HttpClientException
 * @throws ReflectionException
 * @throws BaseException
 * @throws ConfigException
 * @throws DiException
 */
function save_remote_image(string $imageUrl, string $userDirectory, string $imageName): string
{
    $fs = FileSystemFactory::get();

    $imageName = slugify($imageName) . '.jpg';

    $httpClient = httpRequest($imageUrl)
        ->setMethod('GET')
        ->setOpt(CURLOPT_FOLLOWLOCATION, true)
        ->setOpt(CURLOPT_SSL_VERIFYPEER, false) // public demo images only, so a PHP without a CA bundle still works
        ->start();

    $fs->put(
        uploads_dir() . DS . $userDirectory . DS . $imageName,
        $httpClient->getResponseBody()
    );

    return $imageName;
}

/**
 * Saves remote images
 * @param array<int, array{url: string, directory: string, name: string}> $images
 * @return array<int, string>
 * @throws ReflectionException
 * @throws BaseException
 * @throws ConfigException
 * @throws DiException
 */
function save_remote_images(array $images): array
{
    if ($images === []) {
        return [];
    }

    $fs = FileSystemFactory::get();
    // The certificate is not verified: public demo images only, so a PHP without a CA bundle still works
    $multiCurl = (new MultiCurlAdapter())->setOpts([
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $jobs = [];
    $imageNames = [];

    foreach ($images as $index => $image) {
        /** @var CurlAdapterInterface $request */
        $request = $multiCurl->addGet($image['url']);
        $imageNames[$index] = slugify($image['name']) . '.jpg';
        $jobs[$request->getId()] = [
            'directory' => $image['directory'],
            'name' => $imageNames[$index],
        ];
    }

    $multiCurl->complete(function (CurlAdapterInterface $request) use ($fs, $jobs): void {
        if (!isset($jobs[$request->getId()])) {
            return;
        }

        if ($request->isError()) {
            throw new RuntimeException(
                $request->getErrorMessage() ?? 'Unable to download remote image',
                $request->getErrorCode()
            );
        }

        $job = $jobs[$request->getId()];

        $fs->put(
            uploads_dir() . DS . $job['directory'] . DS . $job['name'],
            $request->getResponse()
        );
    });

    $multiCurl->start();

    return $imageNames;
}

/**
 * Creates user directory
 * @param string $uuid
 * @throws DiException
 * @throws ReflectionException
 * @throws BaseException
 * @throws ConfigException
 */
function create_user_directory(string $uuid): void
{
    $userDirectory = uploads_dir() . DS . $uuid;

    if (!fs()->isDirectory($userDirectory)) {
        fs()->makeDirectory($userDirectory);
    }
}

/**
 * Cleans up text for titles/descriptions.
 * @param string $text
 * @return string
 */
function textCleanUp(string $text): string
{
    return str_replace(['"', '\'', '-'], '', $text);
}

/**
 * Encodes current query string to URL-safe base64.
 * @param string|null $query
 * @return string
 */
function nav_ref_encode(?string $query): string
{
    return $query ? rtrim(strtr(base64_encode($query), '+/', '-_'), '=') : '';
}

/**
 * Decodes a URL-safe base64 reference back to query string.
 *
 * @param string|null $ref
 * @return string
 */
function nav_ref_decode(?string $ref): string
{
    if ($ref === 'my-posts') {
        return '/my-posts';
    }

    $decoded = $ref ? base64_decode(strtr($ref, '-_', '+/'), true) : false;

    return '/posts' . ($decoded ? '?' . $decoded : '');
}
