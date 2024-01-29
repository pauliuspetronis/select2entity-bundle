<?php

declare(strict_types=1);

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractEntityToPropertyTransformer implements DataTransformerInterface
{
    protected PropertyAccessor $accessor;

    public function __construct(
        protected EntityManagerInterface $em,
        protected string $className,
        protected ?string $textProperty = null,
        protected string $primaryKey = 'id',
        protected string $newTagPrefix = '__',
        protected string $newTagText = ' (NEW)'
    ) {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    protected function getKeyAndText(mixed $value): array
    {
        $text = is_null($this->textProperty)
            ? (string)$value
            : $this->accessor->getValue($value, $this->textProperty);

        if ($this->em->contains($value)) {
            $key = (string)$this->accessor->getValue($value, $this->primaryKey);
        } else {
            $key = $this->newTagPrefix . $text;
            $text .= $this->newTagText;
        }

        return [$key, $text];
    }
}
