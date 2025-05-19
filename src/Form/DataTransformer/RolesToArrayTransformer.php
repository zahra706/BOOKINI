<?php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class RolesToArrayTransformer implements DataTransformerInterface
{
    public function transform($roles): ?string
    {
        // ex: ['ROLE_USER'] → 'ROLE_USER'
        return $roles[0] ?? null;
    }

    public function reverseTransform($role): array
    {
        // ex: 'ROLE_USER' → ['ROLE_USER']
        return [$role];
    }
}
