<?php
namespace Moss\Storage\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        $dbal = $this->mockDBAL();
        $bag = $this->mockModelBag();
        $mutator = $this->mockMutator();

        $query = new Query($dbal, $bag, $mutator);

        $this->assertSame($dbal, $query->connection());
    }

    public function testNum()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('addSelect')->with('`foo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->num('entity');
    }

    public function testRead()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity');
    }

    public function testReadOne()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('setMaxResults')->with(1);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->readOne('entity');
    }

    public function testWrite()
    {
        $this->markTestIncomplete();
    }

    public function testInsert()
    {
        $this->markTestIncomplete();
    }

    public function testUpdate()
    {
        $this->markTestIncomplete();
    }

    public function testDelete()
    {
        $this->markTestIncomplete();
    }

    public function testClear()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('delete')->with('`table`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->clear('entity');
    }

    public function testFields()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]); // resets fields
        $builder->expects($this->at(6))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(7))->method('addSelect')->with('`foo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->fields(['bar', 'foo']);
    }

    public function testField()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('select')->with([]); // resets fields
        $builder->expects($this->at(6))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(7))->method('addSelect')->with('`bar`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->fields(['foo'])->field('bar');
    }

    public function testDistinct()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('DISTINCT(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->distinct('foo', 'distFoo');
    }

    public function testCount()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('COUNT(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->count('foo', 'distFoo');
    }

    public function testAverage()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('AVERAGE(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->average('foo', 'distFoo');
    }

    public function testMax()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('MAX(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->max('foo', 'distFoo');
    }

    public function testMin()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('MIN(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->min('foo', 'distFoo');
    }

    public function testSum()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->at(0))->method('select')->with();
        $builder->expects($this->at(1))->method('from')->with('`table`');
        $builder->expects($this->at(2))->method('select')->with([]); // resets fields
        $builder->expects($this->at(3))->method('addSelect')->with('`foo`');
        $builder->expects($this->at(4))->method('addSelect')->with('`bar`');
        $builder->expects($this->at(5))->method('addSelect')->with('SUM(`foo`) AS `distFoo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->sum('foo', 'distFoo');
    }

    public function testGroup()
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('addGroupBy')->with('`foo`');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')->group('foo');
    }

    public function testValues()
    {
        $this->markTestIncomplete();
    }

    public function testValue()
    {
        $this->markTestIncomplete();
    }

    /**
     * @dataProvider conditionStructureProvider
     */
    public function testWhereStructure($field, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where($field, $value);
    }

    /**
     * @dataProvider conditionComparisonOperatorProvider
     */
    public function testWhereComparisonOperators($operator, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andWhere')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where('foo', $value, $operator);
    }

    /**
     * @dataProvider conditionLogicalOperatorProvider
     */
    public function testWhereLogicalOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method($expected . 'Where')->with('`foo` = :condition_0_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->where('foo', 1, '=', $operator);
    }

    /**
     * @dataProvider conditionStructureProvider
     */
    public function testHavingStructure($field, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andHaving')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having($field, $value);
    }

    /**
     * @dataProvider conditionComparisonOperatorProvider
     */
    public function testHavingComparisonOperators($operator, $value, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('andHaving')->with($expected);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo', 'bar'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having('foo', $value, $operator);
    }

    /**
     * @dataProvider conditionLogicalOperatorProvider
     */
    public function testHavingLogicalOperators($operator, $expected)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method($expected . 'Having')->with('`foo` = :condition_0_foo');

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->having('foo', 1, '=', $operator);
    }

    public function conditionStructureProvider()
    {
        return [
            ['foo', 1, '`foo` = :condition_0_foo'],
            ['foo', [1, 2], '(`foo` = :condition_0_foo or `foo` = :condition_1_foo)'],
            [['foo', 'bar'], 1, '(`foo` = :condition_0_foo and `bar` = :condition_1_bar)'],
            [['foo', 'bar'], [1, 2], '((`foo` = :condition_0_foo or `foo` = :condition_1_foo) and (`bar` = :condition_2_bar or `bar` = :condition_3_bar))'],
        ];
    }

    public function conditionComparisonOperatorProvider()
    {
        return [
            ['=', 1, '`foo` = :condition_0_foo'],
            ['!=', 1, '`foo` != :condition_0_foo'],
            ['=', null, '`foo` IS NULL'],
            ['!=', null, '`foo` IS NOT NULL'],
            ['<', 1, '`foo` < :condition_0_foo'],
            ['<=', 1, '`foo` <= :condition_0_foo'],
            ['>', 1, '`foo` > :condition_0_foo'],
            ['>=', 1, '`foo` >= :condition_0_foo'],
            ['like', 1, '`foo` like :condition_0_foo'],
            ['regexp', 1, '`foo` regexp :condition_0_foo'],
        ];
    }

    public function conditionLogicalOperatorProvider()
    {
        return [
            ['and', 'and'],
            ['or', 'or'],
        ];
    }

    /**
     * @dataProvider orderProvider
     */
    public function testOrder($order)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('addOrderBy')->with('`foo`', $order);

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->order('foo', $order);
    }

    public function orderProvider()
    {
        return [
            ['asc'],
            ['desc']
        ];
    }

    /**
     * @dataProvider limitProvider
     */
    public function testLimit($limit, $offset)
    {
        $builder = $this->mockQueryBuilder();
        $builder->expects($this->once())->method('setMaxResults')->with($limit);

        if($offset) {
            $builder->expects($this->once())->method('setFirstResult')->with($offset);
        }

        $dbal = $this->mockDBAL($builder);

        $bag = $this->mockModelBag(
            [
                'entity' => [
                    '\\stdClass',
                    'table',
                    ['foo'],
                    ['foo']
                ]
            ]
        );

        $mutator = $this->mockMutator();


        $query = new Query($dbal, $bag, $mutator);
        $query->read('entity')
            ->limit($limit, $offset);
    }

    public function limitProvider()
    {
        return [
            [0, 0],
            [0, 10],
            [10, 0],
            [10, 20]
        ];
    }

    public function testWith()
    {
        $this->markTestIncomplete();
    }

    public function testRelation()
    {
        $this->markTestIncomplete();
    }

    public function testExecute()
    {
        $this->markTestIncomplete();
    }

    public function testQueryString()
    {
        $this->markTestIncomplete();
    }

    public function testReset()
    {
        $this->markTestIncomplete();
    }

    /**
     * @return \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockDBAL($queryBuilderMock = null)
    {
        $queryBuilderMock = $queryBuilderMock ?: $this->mockQueryBuilder();

        $dbalMock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $dbalMock->expects($this->any())->method('quoteIdentifier')->will($this->returnCallback(
            function ($val) {
                return sprintf('`%s`', $val);
            })
        );
        $dbalMock->expects($this->any())->method('createQueryBuilder')->will($this->returnValue($queryBuilderMock));

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
     * @return \Moss\Storage\Model\ModelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModelBag($models = [])
    {
        $modelMap = [];
        foreach ($models as $alias => $model) {
            list($entity, $table, $fields, $primary, $index) = $model + [null, null, [], [], []];

            $modelMap[] = [$alias, $this->mockModel($entity, $table, $fields, $primary, $index)];
        }

        $bagMock = $this->getMock('\Moss\Storage\Model\ModelBag');
        $bagMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $bagMock->expects($this->any())->method('get')->will($this->returnValueMap($modelMap));

        return $bagMock;
    }

    /**
     * @return \Moss\Storage\Model\ModelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockModel($entity, $table, $fields = [], $primaryFields = [], $indexFields = [])
    {
        $fieldsMap = [];
        $indexFields = array_merge($indexFields, $primaryFields); // because all primary fields are index fields

        foreach ($fields as $i => $field) {
            list($name, $type, $attributes) = (array) $field + [null, 'string', []];
            $mock = $this->mockField($name, $type, $attributes);

            $fields[$i] = $mock;
            $fieldsMap[$mock->name()] = [$mock->name(), $mock];
        }

        foreach($primaryFields as $i => $field) {
            $primaryFields[$i] = $fieldsMap[$field][1];
        }

        foreach($indexFields as $i => $field) {
            $indexFields[$i] = $fieldsMap[$field][1];
        }

        $modelMock = $this->getMock('\Moss\Storage\Model\ModelInterface');

        $modelMock->expects($this->any())->method('table')->will($this->returnValue($table));
        $modelMock->expects($this->any())->method('entity')->will($this->returnValue($entity));

        $modelMock->expects($this->any())->method('isPrimary')->will($this->returnCallback(
            function ($field) use ($primaryFields) {
                return in_array($field, $primaryFields);
            })
        );
        $modelMock->expects($this->any())->method('primaryFields')->will($this->returnValue($primaryFields));

        $modelMock->expects($this->any())->method('isIndex')->will($this->returnCallback(
            function ($field) use ($indexFields) {
                return in_array($field, $indexFields);
            })
        );
        $modelMock->expects($this->any())->method('indexFields')->will($this->returnValue($indexFields));

        $modelMock->expects($this->any())->method('field')->will($this->returnValueMap($fieldsMap));
        $modelMock->expects($this->any())->method('fields')->will($this->returnValue($fields));

        return $modelMock;
    }

    /**
     * @return \Moss\Storage\Model\Definition\FieldInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockField($name, $type, $attributes = [])
    {
        $fieldMock = $this->getMock('\Moss\Storage\Model\Definition\FieldInterface');
        $fieldMock->expects($this->any())->method('name')->will($this->returnValue($name));
        $fieldMock->expects($this->any())->method('type')->will($this->returnValue($type));

        $fieldMock->expects($this->any())->method('attribute')->will($this->returnCallback(
            function ($key) use ($attributes) {
                return array_key_exists($key, $attributes) ? $attributes[$key] : false;
            })
        );

        $fieldMock->expects($this->any())->method('attributes')->will($this->returnValue($attributes));

        return $fieldMock;
    }

    /**
     * @return \Moss\Storage\Mutator\MutatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockMutator()
    {
        $mutatorMock = $this->getMock('\Moss\Storage\Mutator\MutatorInterface');
        $mutatorMock->expects($this->any())->method($this->anything())->will($this->returnArgument(0));

        return $mutatorMock;
    }
}
