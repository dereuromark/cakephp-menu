<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Menu\Item\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;
use function array_key_exists;
use function is_array;

class UrlArrayResolver implements ResolverInterface
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
        protected ServerRequestInterface $request,
        array $options = [],
    ) {
        $this->setConfig($options);
    }

    public function resolve(ItemInterface $item): void
    {
        $link = $item->getLink();
        if ($link === null) {
            return;
        }

        $linkArray = $link->getRawUrl();
        if (!is_array($linkArray)) {
            return;
        }

        $requestParams = (array)$this->request->getAttribute('params');
        if ($this->matches($requestParams, $linkArray)) {
            $item->setActive(true);
        }
    }

    /**
     * @phpstan-param array<string, mixed> $requestParams
     * @phpstan-param array<string|int, mixed> $linkArray
     */
    protected function matches(array $requestParams, array $linkArray): bool
    {
        foreach (['plugin', 'prefix', 'controller', 'action'] as $key) {
            if (array_key_exists($key, $linkArray) && ($requestParams[$key] ?? null) !== $linkArray[$key]) {
                return false;
            }
        }

        foreach ($linkArray as $key => $value) {
            if (!is_int($key)) {
                continue;
            }
            if (($requestParams['pass'][$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }
}
