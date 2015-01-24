<?php
namespace tests\Moss\Storage\Query;

abstract class QueryMocks extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockDBAL($queryBuilderMock = null)
    {
        $queryBuilderMock = $queryBuilderMock ?: $this->mockQueryBuilder();

        $dbalMock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $dbalMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will(
                $this->returnCallback(
                    function ($val) {
                        return sprintf('`%s`', $val);
                    }
                )
            );
        $dbalMock->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilderMock));

        return $dbalMock;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockQueryBuilder()
    {
        return $this->getMockBuilder('\Doctrine\DBAL\Query\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \Moss\Storage\Query\Relation\RelationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockRelation()
    {
        $relationMock = $this->getMock('\Moss\Storage\Query\Relation\RelationInterface');

        return $relationMock;
    }

    /**
     * @return \Moss\Storage\Query\Relation\RelationFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockRelFactory()
    {
        $factoryMock = $this->getMock('\Moss\Storage\Query\Relation\RelationFactoryInterface');

        $factoryMock->expects($this->any())
            ->method('splitRelationName')
            ->will($this->returnCallback(function ($arg) { return array_merge(explode('.', $arg), ['', null]); }));

        return $factoryMock;
    }

    /**
     * @return \Moss\Storage\Model\ModelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModel($entity, $table, $fields = [], $primaryFields = [], $indexFields = [])
    {
        $fieldsMap = [];
        $indexFields = array_merge($indexFields, $primaryFields); // because all primary fields are index fields

        foreach ($fields as $i => $field) {
            list($name, $type, $attributes, $mapping) = (array) $field + [null, 'string', [], null];
            $mock = $this->mockField($name, $type, $attributes, $mapping);

            $fields[$i] = $mock;
            $fieldsMap[$mock->name()] = [$mock->name(), $mock];
        }

        foreach ($primaryFields as $i => $field) {
            $primaryFields[$i] = $fieldsMap[$field][1];
        }

        foreach ($indexFields as $i => $field) {
            $indexFields[$i] = $fieldsMap[$field][1];
        }

        $modelMock = $this->getMock('\Moss\Storage\Model\ModelInterface');

        $modelMock->expects($this->any())
            ->method('table')
            ->will($this->returnValue($table));
        $modelMock->expects($this->any())
            ->method('entity')
            ->will($this->returnValue($entity));
        $modelMock->expects($this->any())
            ->method('referredIn')
            ->will($this->returnValue([]));

        $modelMock->expects($this->any())
            ->method('isPrimary')
            ->will(
                $this->returnCallback(
                    function ($field) use ($primaryFields) {
                        return in_array($field, $primaryFields);
                    }
                )
            );
        $modelMock->expects($this->any())
            ->method('primaryFields')
            ->will($this->returnValue($primaryFields));

        $modelMock->expects($this->any())
            ->method('isIndex')
            ->will(
                $this->returnCallback(
                    function ($field) use ($indexFields) {
                        return in_array($field, $indexFields);
                    }
                )
            );
        $modelMock->expects($this->any())
            ->method('indexFields')
            ->will($this->returnValue($indexFields));

        $modelMock->expects($this->any())
            ->method('field')
            ->will($this->returnValueMap($fieldsMap));
        $modelMock->expects($this->any())
            ->method('fields')
            ->will($this->returnValue($fields));

        return $modelMock;
    }

    /**
     * @return \Moss\Storage\Model\Definition\FieldInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockField($name, $type, $attributes = [], $mapping = null)
    {
        $fieldMock = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $fieldMock->expects($this->any())
            ->method('name')
            ->will($this->returnValue($name));
        $fieldMock->expects($this->any())
            ->method('type')
            ->will($this->returnValue($type));
        $fieldMock->expects($this->any())
            ->method('mapping')
            ->will($this->returnValue($mapping));

        $fieldMock->expects($this->any())
            ->method('attribute')
            ->will(
                $this->returnCallback(
                    function ($key) use ($attributes) {
                        return array_key_exists($key, $attributes) ? $attributes[$key] : false;
                    }
                )
            );

        $fieldMock->expects($this->any())
            ->method('attributes')
            ->will($this->returnValue($attributes));

        return $fieldMock;
    }

    /**
     * @return \Moss\Storage\Converter\ConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockConverter()
    {
        $ConverterMock = $this->getMock('\Moss\Storage\Converter\ConverterInterface');
        $ConverterMock->expects($this->any())
            ->method($this->anything())
            ->will($this->returnArgument(0));

        return $ConverterMock;
    }
}
