<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Annotations;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class GuardArgument
{
    /**
     * @var string
     */
    public $argumentName = '';

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['value']) && !isset($values['argumentName'])) {
            throw new \InvalidArgumentException(
                'A GuardArgument annotation must specify an argument name.',
                1562935607
            );
        }
        $this->argumentName = $values['argumentName'] ?? $values['value'];
    }
}
