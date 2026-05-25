<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Menu\Item\ItemInterface;
use Psr\Http\Message\RequestInterface;
use function parse_url;

class Psr7UrlResolver implements ResolverInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function __construct(
        protected RequestInterface $request,
        array $options = [],
    ) {
        $this->setConfig($options);
    }

    public function resolve(ItemInterface $item): void
    {
        $link = $item->getLink();
        if ($link === null || is_array($link->getRawUrl())) {
            return;
        }

        $requestUri = (string)$this->request->getUri();
        $linkUrl = $link->getUrl();
        if ($linkUrl === null) {
            return;
        }

        if ($requestUri === $linkUrl) {
            $item->setActive(true);

            return;
        }

        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        $requestQuery = parse_url($requestUri, PHP_URL_QUERY);
        $linkPath = parse_url($linkUrl, PHP_URL_PATH);
        $linkQuery = parse_url($linkUrl, PHP_URL_QUERY);

        if ($requestPath === $linkPath && $requestQuery === $linkQuery) {
            $item->setActive(true);
        }
    }
}
