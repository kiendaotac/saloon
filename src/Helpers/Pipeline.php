<?php

declare(strict_types=1);

namespace Saloon\Helpers;

use Saloon\Data\Pipe;
use Saloon\Enums\Order;
use Saloon\Data\PipeOrder;
use Saloon\Exceptions\DuplicatePipeNameException;
use Saloon\Contracts\Pipeline as PipelineContract;

class Pipeline implements PipelineContract
{
    /**
     * The pipes in the pipeline.
     *
     * @var array<\Saloon\Data\Pipe>
     */
    protected array $pipes = [];

    /**
     * Add a pipe to the pipeline
     *
     * @param callable(mixed $payload): (mixed) $callable
     * @return $this
     * @throws \Saloon\Exceptions\DuplicatePipeNameException
     */
    public function pipe(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        $pipe = new Pipe($callable, $name, $order);

        if (is_string($name) && $this->pipeExists($name)) {
            throw new DuplicatePipeNameException($name);
        }

        $this->pipes[] = $pipe;

        return $this;
    }

    /**
     * Process the pipeline.
     */
    public function process(mixed $payload): mixed
    {
        foreach ($this->sortPipes() as $pipe) {
            $payload = call_user_func($pipe->callable, $payload);
        }

        return $payload;
    }

    /**
     * Sort the pipes based on the "order" classes
     *
     * @return array<\Saloon\Data\Pipe>
     */
    protected function sortPipes(): array
    {
        $firstPipes = [];
        $nullPipes = [];
        $lastPipes = [];

        // We'll simply loop through each pipe and add them to their respective
        // arrays based on the order type. We'll then merge the arrays.

        foreach ($this->pipes as $pipe) {
            match ($pipe->order?->type) {
                Order::FIRST => $firstPipes[] = $pipe,
                null => $nullPipes[] = $pipe,
                Order::LAST => $lastPipes[] = $pipe,
            };
        }

        return array_merge($firstPipes, $nullPipes, $lastPipes);
    }

    /**
     * Set the pipes on the pipeline.
     *
     * @param array<\Saloon\Data\Pipe> $pipes
     * @return $this
     * @throws \Saloon\Exceptions\DuplicatePipeNameException
     */
    public function setPipes(array $pipes): static
    {
        $this->pipes = [];

        // Loop through each of the pipes and manually add each pipe
        // so we can check if the name already exists.

        foreach ($pipes as $pipe) {
            $this->pipe($pipe->callable, $pipe->name, $pipe->order);
        }

        return $this;
    }

    /**
     * Get all the pipes in the pipeline
     *
     * @return array<\Saloon\Data\Pipe>
     */
    public function getPipes(): array
    {
        return $this->pipes;
    }

    /**
     * Check if a given pipe exists for a name
     */
    protected function pipeExists(string $name): bool
    {
        foreach ($this->pipes as $pipe) {
            if ($pipe->name === $name) {
                return true;
            }
        }

        return false;
    }
}
