<?php declare(strict_types=1);

namespace Sammyjo20\Saloon\Traits;

use ReflectionException;
use Sammyjo20\Saloon\Http\SaloonRequest;
use Sammyjo20\Saloon\Helpers\ReflectionHelper;
use Sammyjo20\Saloon\Exceptions\SaloonInvalidConnectorException;
use Sammyjo20\Saloon\Exceptions\SaloonInvalidResponseClassException;

trait HasCustomResponses
{
    /**
     * Specify a default response.
     *
     * @var string
     */
    protected string $defaultResponse = '';

    /**
     * Get the response class
     *
     * @return string
     * @throws ReflectionException
     * @throws SaloonInvalidConnectorException
     * @throws SaloonInvalidResponseClassException
     */
    public function getResponseClass(): string
    {
        $baseResponse = $this->sender()->getResponseClass();
        $response = $this->resolveResponse();

        if (empty($response)) {
            $response = $this instanceof SaloonRequest ? $this->connector()->getResponseClass() : $baseResponse;
        }

        if (! class_exists($response)) {
            throw new SaloonInvalidResponseClassException;
        }

        if (! ReflectionHelper::isSubclassOf($response, $baseResponse)) {
            throw new SaloonInvalidResponseClassException(sprintf('The custom response must extend the "%s" class.', $baseResponse));
        }

        return $response;
    }

    /**
     * Resolve the custom response class
     *
     * @return string
     */
    protected function resolveResponse(): string
    {
        return $this->defaultResponse;
    }
}
