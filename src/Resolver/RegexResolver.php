<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use Cake\Core\InstanceConfigTrait;
use CakeMenu\Item\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;
use function is_string;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;

class RegexResolver implements ResolverInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = ['dataKey' => 'match', 'maxDepth' => null];

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array<string, mixed> $options
     */
    public function __construct(protected ServerRequestInterface $request, array $options = [])
    {
        $this->setConfig($options);
    }

    public function resolve(ItemInterface $item, ResolverContext $context): void
    {
        $maxDepth = $this->getConfig('maxDepth');
        if (is_int($maxDepth) && $context->getDepth() > $maxDepth) {
            return;
        }
        $patterns = $item->getData((string)$this->getConfig('dataKey'));
        if ($patterns === null) {
            return;
        }

        foreach ((array)$patterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            if ($this->matches($pattern)) {
                $item->setRuntimeActive(true);

                return;
            }
        }
    }

    protected function matches(string $pattern): bool
    {
        set_error_handler(static fn (): bool => true);
        try {
            return preg_match($pattern, $this->request->getUri()->getPath()) === 1;
        } finally {
            restore_error_handler();
        }
    }
}
