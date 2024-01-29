<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Data transformer for multiple mode (i.e., multiple = true)
 *
 * Class EntitiesToPropertyTransformer
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntitiesToPropertyTransformer extends AbstractEntityToPropertyTransformer
{
    /**
     * Transform initial entities to array
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

        foreach ($value as $val) {
            [$key, $text] = $this->getKeyAndText($val);
            $data[$key] = $text;
        }

        return $data;
    }

    /**
     * Transform array to a collection of entities
     *
     * @param array $value
     * @return array
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (!is_array($value) || empty($value)) {
            return [];
        }

        // add new tag entries
        $newObjects = [];
        $tagPrefixLength = strlen($this->newTagPrefix);
        foreach ($value as $key => $val) {
            $cleanValue = substr($val, $tagPrefixLength);
            $valuePrefix = substr($val, 0, $tagPrefixLength);
            if ($valuePrefix === $this->newTagPrefix) {
                $object = new $this->className;
                $this->accessor->setValue($object, $this->textProperty, $cleanValue);
                $newObjects[] = $object;
                unset($value[$key]);
            }
        }

        // get multiple entities with one query
        $entities = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->className, 'entity')
            ->where('entity.' . $this->primaryKey . ' IN (:ids)')
            ->setParameter('ids', $value)
            ->getQuery()
            ->getResult();

        // this will happen if the form submits invalid data
        if (count($entities) !== count($value)) {
            throw new TransformationFailedException('One or more id values are invalid');
        }

        return array_merge($entities, $newObjects);
    }
}
