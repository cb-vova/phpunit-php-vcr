<?php

declare(strict_types=1);

namespace Angelov\PHPUnitPHPVcr\Subscribers;

use Angelov\PHPUnitPHPVcr\UseCassette;
use Angelov\PHPUnitPHPVcr\Values\TestCaseParameters;
use Angelov\PHPUnitPHPVcr\Values\TestMethodInfo;
use Exception;
use ReflectionMethod;

trait AttributeResolverTrait
{
    private function needsRecording(string $test): bool
    {
        return $this->getTestCaseCassetteParameters($test) !== null;
    }

    private function getTestCaseCassetteParameters(string $test): ?TestCaseParameters
    {
        $testMethodDetails = $this->parseMethod($test);

        try {
            if (PHP_VERSION_ID < 80300) {
                $method = new ReflectionMethod($testMethodDetails->method);
            } else {
                // @phpstan-ignore-next-line
                $method = ReflectionMethod::createFromMethodName($testMethodDetails->method);
            }
        } catch (Exception) {
            return null;
        }

        $attributes = $method->getAttributes(UseCassette::class);

        $cassette = $attributes ? $attributes[0]->newInstance() : $this->getAttributeFromClass($testMethodDetails);
        if ($cassette === null) {
            return null;
        }

        return new TestCaseParameters(
            cassette: $cassette,
            case: $testMethodDetails->dataProvider,
        );
    }

    private function parseMethod(string $test): TestMethodInfo
    {
        $methoDetails = explode("#", $test);
        return new TestMethodInfo(
            method: $methoDetails[0],
            dataProvider: $methoDetails[1] ?? null
        );
    }

    private function getAttributeFromClass(TestMethodInfo $test): ?UseCassette
    {
        if (PHP_VERSION_ID < 80300) {
            $method = new ReflectionMethod($test->method);
        } else {
            // @phpstan-ignore-next-line
            $method = ReflectionMethod::createFromMethodName($test->method);
        }
        $class = $method->getDeclaringClass();
        $attributes = $class->getAttributes(UseCassette::class);

        if ($attributes) {
            return $attributes[0]->newInstance();
        }

        return null;
    }
}
