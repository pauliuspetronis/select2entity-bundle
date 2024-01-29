<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Data transformer for single mode (i.e., multiple = false)
 *
 * Class EntityToPropertyTransformer
 *
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntityToPropertyTransformer extends AbstractEntityToPropertyTransformer
{
    /**
     * Transform entity to array
     *
     * @param mixed $value
     * @return array
     */
    public function transform(mixed $value): array
    {
        $data = [];
        if (empty($value)) {
            return $data;
        }

        [$key, $text] = $this->getKeyAndText($value);

        $data[$key] = $text;

        return $data;
    }

    /**
     * Transform single id value to an entity
     *
     * @param string $value
     * @return mixed|null|object
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (empty($value)) {
            return null;
        }

        // Add a potential new tag entry
        $tagPrefixLength = strlen($this->newTagPrefix);
        $cleanValue = substr($value, $tagPrefixLength);
        $valuePrefix = substr($value, 0, $tagPrefixLength);
        if ($valuePrefix == $this->newTagPrefix) {
            // In that case, we have a new entry
            $entity = new $this->className;
            $this->accessor->setValue($entity, $this->textProperty, $cleanValue);
        } else {
            // We do not search for a new entry, as it does not exist yet, by definition
            try {
                $entity = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from($this->className, 'entity')
                    ->where('entity.' . $this->primaryKey . ' = :id')
                    ->setParameter('id', $value)
                    ->getQuery()
                    ->getSingleResult();
            } catch (\Doctrine\ORM\UnexpectedResultException $ex) {
                // this will happen if the form submits invalid data
                throw new TransformationFailedException(sprintf('The choice "%s" does not exist or is not unique', $value));
            }
        }

        if (!$entity) {
            return null;
        }

        return $entity;
    }
}
