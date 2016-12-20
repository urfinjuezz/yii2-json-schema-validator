<?php
namespace urfinjuezz\yii2jsv;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator as JSValidator;
use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * JsonSchemaValidator validates a value against a JSON Schema file.
 *
 * The URI of the schema file must be defined via the [[schema]] property.
 *
 * @author David Stotijn <dstotijn@gmail.com>
 */
class JsonSchemaValidator extends Validator
{
    /**
     * @var string The URI of the JSON schema file.
     */
    public $schema;

    /**
     * @var string User-defined error message used when the schema is missing.
     */
    public $schemaEmpty;

    /**
     * @var string User-defined error message used when the schema isn't a string.
     */
    public $schemaNotString;

    /**
     * @var string User-defined error message used when the value is not a string.
     */
    public $notString;

    /**
     * @var string User-defined error message used when the value is not a valid JSON string.
     */
    public $notJsonString;

    /**
     * @var boolean if attribute is json string
     */
    public $validateJson;

    /**
     * @var string User-defined error message used when the value is not an array
     */
    public $notArray;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->schemaEmpty === null) {
            $this->schemaEmpty = 'The "schema" property must be set.';
        }

        if ($this->schemaNotString === null) {
            $this->schemaNotString = 'The "schema" property must be a a string.';
        }

        if ($this->message === null) {
            $this->message = Yii::t('app', '{property}: {message}.');
        }

        if ($this->notString === null) {
            $this->notString = Yii::t('app', 'The value must be a string.');
        }

        if ($this->notJsonString === null) {
            $this->notJsonString = Yii::t('app', 'The value must be a valid JSON string.');
        }

        if (empty($this->schema)) {
            throw new InvalidConfigException($this->schemaEmpty);
        }

        if (!is_string($this->schema)) {
            throw new InvalidConfigException($this->schemaNotString);
        }

        if (null === $this->validateJson) {
            $this->validateJson = false;
        } elseif (!is_bool($this->validateJson)) {
            throw new InvalidConfigException(Yii::t('app', 'validateJson must be boolean'));
        }

        if (null === $this->notArray) {
            $this->notArray = Yii::t('app', 'The value must be an array.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        if ($this->validateJson) {
            if (!is_string($model->$attribute)) {
                $this->addError($model, $attribute, $this->notString);
                return;
            }
            $value = json_decode($model->$attribute);

            if (json_last_error()) {
                $this->addError($model, $attribute, $this->notJsonString);
            }
        } else {
            if (is_array($model->$attribute)) {
                $value = (object)$model->$attribute;
            } elseif (!is_object($model->$attribute)) {
                $this->addError($model, $attribute, $this->notArray);
                return;
            } else {
                $value = $model->$attribute;
            }
        }

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve($this->schema);

        $validator = new JSValidator();
        $validator->check($value, $schema);

        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->addError(
                    $model,
                    $attribute,
                    $this->message,
                    [
                        'property' => $error['property'],
                        'message' => ucfirst($error['message']),
                    ]
                );
            }
        }
    }

    /**
     * Validates a value.
     *
     * @param array|string $value An array or JSON encoded string to validate.
     * @return array|null An array of error data, or null if the data is valid.
     */
    protected function validateValue($value)
    {

        if ($this->validateJson) {
            if (!is_string($value)) {
                return [$this->notString, []];
            }
            $value = json_decode($value);

            if (json_last_error()) {
                return [$this->notJsonString, []];
            }
        } else {
            if (is_array($value)) {
                $value = (object)$value;
            } elseif (!is_object($value)) {
                return [$this->notArray, []];
            }
        }

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve($this->schema);

        $validator = new JSValidator();
        $validator->check($value, $schema);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $error = reset($errors);
            return [$this->message, ['property' => $error['property'], 'message' => ucfirst($error['message'])]];
        }

        return null;
    }
}
