<?php

declare(strict_types=1);

namespace kuiper\db\exception;

class ModelNotFoundException extends \InvalidArgumentException implements Exception
{
    /**
     * Name of the affected model.
     *
     * @var string
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var int|array
     */
    protected $ids;

    /**
     * Set the affected model and instance ids.
     *
     * @param string    $model
     * @param int|array $ids
     *
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = (array) $ids;

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    /**
     * Get the affected model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected model IDs.
     *
     * @return int|array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
