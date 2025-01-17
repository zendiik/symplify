<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Tests\Symfony\Rules\RequireCascadeValidateRule\Fixture;

use Symplify\PHPStanRules\Tests\Symfony\Rules\RequireCascadeValidateRule\Source\SomeEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class SomeFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(
            [
                'data_class' => SomeEntity::class,
                'constraints' => new Valid(),
            ]
        );
    }
}
