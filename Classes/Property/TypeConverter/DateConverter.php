<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Property\TypeConverter;

use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\DateTimeConverter;
use Neos\Flow\Validation\Error;

class DateConverter extends DateTimeConverter
{
    /**
     * @var string
     */
    const DEFAULT_DATE_FORMAT = 'Y-m-d';

    /**
     * @var string
     */
    const DEFAULT_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var integer
     */
    protected $priority = 2;

    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $configuration->setTypeConverterOption(DateTimeConverter::class, self::CONFIGURATION_DATE_FORMAT, self::DEFAULT_DATE_FORMAT);
        $date = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);
        if ($date instanceof Error) {
            $configuration->setTypeConverterOption(DateTimeConverter::class, self::CONFIGURATION_DATE_FORMAT, self::DEFAULT_DATE_TIME_FORMAT);
            $date = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);
        }
        return $date;
    }
}
