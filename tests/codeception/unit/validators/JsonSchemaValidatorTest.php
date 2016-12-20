<?php

namespace tests\codeception\unit\models;

use urfinjuezz\yii2jsv\JsonSchemaValidator;
use urfinjuezz\yii2jsv\tests\data\models\Car;
use urfinjuezz\yii2jsv\tests\data\models\CarWithBadSchema;
use urfinjuezz\yii2jsv\tests\data\models\CarWithoutSchema;
use yii\codeception\TestCase;

class JsonSchemaValidatorTest extends TestCase
{
    /**
     * @var string The URI of the JSON schema file to use for validation.
     */
    public $schema;

    public function setUp()
    {
        parent::setUp();
        $this->mockApplication();
        $this->schema = 'file://' . __DIR__ . '/../../../data/json_schemas/car.json';
    }

    public function testValidateValue()
    {
        $val = new JsonSchemaValidator(['schema' => $this->schema]);
        $dataString = '{"brand":"Porsche","model":"928S","year":1982}';
        $data = json_decode($dataString);

        $this->assertTrue($val->validate($data));

    }

    public function testValidateValueSchemaEmptyInvalidConfigException()
    {
        $this->setExpectedException('yii\base\InvalidConfigException', 'The "schema" property must be set.');
        $val = new JsonSchemaValidator;
        $val->validate('foobar');
    }

    public function testValidateValueSchemaNotStringInvalidConfigException()
    {
        $this->setExpectedException('yii\base\InvalidConfigException', 'The "schema" property must be a a string.');
        $val = new JsonSchemaValidator(['schema' => true, 'validateJson' => true]);
        $val->validate('foobar');
    }

    public function testValidateValueNotStringFail()
    {
        $val = new JsonSchemaValidator(['schema' => $this->schema, 'validateJson' => true]);
        $val->validate(['foobar'], $error);
        $this->assertEquals('The value must be a string.', $error);
    }

    public function testValidateValueNotArrayFail()
    {
        $val = new JsonSchemaValidator(['schema' => $this->schema]);
        $val->validate('{"brand":"Porsche","model":"928S","year":1982}', $error);
        $this->assertEquals('The value must be an array.', $error);
    }

    public function testValidateValueNotJsonStringFail()
    {
        $val = new JsonSchemaValidator(['schema' => $this->schema, 'validateJson' => true]);
        $val->validate('{]', $error);
        $this->assertEquals('The value must be a valid JSON string.', $error);
    }

    public function testValidateValuePropertyFail()
    {
        $val = new JsonSchemaValidator(['schema' => $this->schema, 'validateJson' => true]);
        $this->assertFalse($val->validate('{"foo":"bar"}', $error));
        $this->assertEquals('brand: The property brand is required.', $error);
    }

    public function testValidateValueRootElementFail()
    {
        $val = new JsonSchemaValidator(['schema' => $this->schema, 'validateJson' => true]);
        $this->assertFalse($val->validate('"foobar"', $error));
        $this->assertEquals(': String value found, but an object is required.', $error);
    }

    public function testValidateAttribute()
    {
        $car = new Car();
        $car->data = '{"brand":"Porsche","model":"928S","year":1982}';
        $this->assertTrue($car->validate());
    }

    public function testValidateAttributeSchemaEmptyInvalidConfigException()
    {
        $this->setExpectedException('yii\base\InvalidConfigException', 'The "schema" property must be set.');
        $car = new CarWithoutSchema();
        $car->validate();
    }

    public function testValidateAttributeSchemaNotStringInvalidConfigException()
    {
        $this->setExpectedException('yii\base\InvalidConfigException', 'The "schema" property must be a a string.');
        $car = new CarWithBadSchema();
        $car->validate();
    }

    public function testValidateEmptyAttribute()
    {
        $car = new Car();
        $car->validate();
        $this->assertEquals('The value must be a string.', $car->getFirstError('data'));
    }

    public function testValidateAttributeNotStringFail()
    {
        $car = new Car();
        $car->data = ['foobar'];
        $car->validate();
        $this->assertEquals('The value must be a string.', $car->getFirstError('data'));
    }

    public function testValidateAttributeNotJsonStringFail()
    {
        $car = new Car();
        $car->data = '{]';
        $car->validate();
        $this->assertEquals('The value must be a valid JSON string.', $car->getFirstError('data'));
    }

    public function testValidateAttributePropertyFail()
    {
        $car = new Car();
        $car->data = '{"foo":"bar"}';
        $car->validate();
        $this->assertEquals('brand: The property brand is required.', $car->getFirstError('data'));
    }

    public function testValidateAttributeRootElementFail()
    {
        $car = new Car();
        $car->data = '"foobar"';
        $car->validate();
        $this->assertEquals(': String value found, but an object is required.', $car->getFirstError('data'));
    }
}
