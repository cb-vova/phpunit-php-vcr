<?php

declare(strict_types=1);

namespace Angelov\PHPUnitPHPVcr\Subscribers;

use Angelov\PHPUnitPHPVcr\UseCassette;
use Angelov\PHPUnitPHPVcr\Values\TestCaseParameters;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;
use VCR\VCR;

class StartRecording implements PreparedSubscriber
{
    use AttributeResolverTrait;

    public function notify(Prepared $event): void
    {
        $test = $event->test()->id();

        if (!$this->needsRecording($test)) {
            return;
        }

        $testCaseCassetteParameters = $this->getTestCaseCassetteParameters($test);
        assert($testCaseCassetteParameters instanceof TestCaseParameters);

        if ($testCaseCassetteParameters->case !== null) {
            $cassetteName = $this->makeCassetteNameForCase(
                case: $testCaseCassetteParameters->case,
                cassette: $testCaseCassetteParameters->cassette,
            );
        } else {
            $cassetteName = $testCaseCassetteParameters->cassette->name;
        }

        VCR::turnOn();
        VCR::insertCassette($cassetteName);
    }

    private function makeCassetteNameForCase(string $case, UseCassette $cassette): string
    {
        if (!$cassette->separateCassettePerCase) {
            return $cassette->name;
        }
        $cassetteNameParts = explode('.', $cassette->name);
        $cassetteSuffix = $cassette->groupCaseFilesInDirectory ? '/' . $case : '-' . $case;
        if (count($cassetteNameParts) === 1) {
            //cassette name does not contain a dot, so we can use it as is
            return $cassette->name . $cassetteSuffix;
        }
        $ext = array_pop($cassetteNameParts);
        return implode('.', $cassetteNameParts) . $cassetteSuffix . '.' . $ext;
    }
}
