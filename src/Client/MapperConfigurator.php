<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\Mapper\Configurator\MapKeysToCamelCase;
use CuyZ\Valinor\Mapper\Configurator\MapperBuilderConfigurator;
use CuyZ\Valinor\MapperBuilder;

/**
 * The mapper configuration that reads Shipmondo's responses into the SDK's response objects. The {@see Client} applies
 * it to every mapper builder, also one passed to its constructor, e.g. one with a cache.
 *
 * Apart from the key converter of {@see MapKeysToCamelCase}, it registers no converters:
 * {@see \Setono\Shipmondo\Response\Resource::$raw} is stamped by a plain assignment in
 * {@see \Setono\Shipmondo\Client\Endpoint\Endpoint::mapItem()}, and a conversion that belongs to one field is an
 * attribute on the field, e.g. {@see \Setono\Shipmondo\Response\NullWhenUnknown}, which Valinor finds without
 * registration.
 */
final class MapperConfigurator implements MapperBuilderConfigurator
{
    public function configureMapperBuilder(MapperBuilder $builder): MapperBuilder
    {
        return $builder
            // Shipmondo's keys are snake_case, the response objects' properties camelCase
            ->configureWith(new MapKeysToCamelCase())
            ->allowScalarValueCasting()
            ->allowNonSequentialList()
            ->allowSuperfluousKeys()
            ->supportDateFormats(
                'Y-m-d\TH:i:sP', // e.g. "2018-10-17T13:25:44Z" (P accepts the Z suffix)
                'Y-m-d\TH:i:s.uP', // e.g. "2018-10-17T13:25:44.557Z" / "...+02:00"
            )
        ;
    }
}
