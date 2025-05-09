<?php

namespace PoweradminInstall\Validators;

use PoweradminInstall\InstallationSteps;
use Symfony\Component\Validator\Constraints as Assert;

class ChooseLanguageValidator extends BaseValidator
{
    public function validate(): array
    {
        $constraints = new Assert\Collection(array_merge(
            $this->getBaseConstraints(),
            [
                'step' => [
                    new Assert\NotBlank(),
                    new Assert\EqualTo([
                        'value' => InstallationSteps::STEP_CHECK_REQUIREMENTS,
                        'message' => 'The step must be equal to ' . InstallationSteps::STEP_CHECK_REQUIREMENTS
                    ])
                ],
            ]
        ));

        $input = $this->request->request->all();
        $violations = $this->validator->validate($input, $constraints);

        return ValidationErrorHelper::formatErrors($violations);
    }
}
