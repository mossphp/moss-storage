<?php
namespace Moss\Storage\Model;

class ModelBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Moss\Storage\Model\ModelException
     * @expectedExceptionMessage Model for entity "Yada" does not exists
     */
    public function testGetUndefined()
    {
        $bag = new ModelBag();
        $bag->get('Yada');
    }

    public function testGetByEntityName()
    {
        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())
            ->method('entity')
            ->will($this->returnValue('Foo'));

        $bag = new ModelBag();
        $bag->set($model);
        $this->assertEquals($model, $bag->get('Foo'));
    }

    public function testGetByAlias()
    {
        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('foofoo'));

        $bag = new ModelBag();
        $bag->set($model, 'foofoo');
        $this->assertEquals($model, $bag->get('foofoo'));
    }

    public function testDoesNotHaveModel()
    {
        $bag = new ModelBag();
        $this->assertFalse($bag->has('Foo'));
    }

    public function testHasByEntityName()
    {
        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())
            ->method('entity')
            ->will($this->returnValue('Foo'));

        $bag = new ModelBag();
        $bag->set($model);
        $this->assertTrue($bag->has('Foo'));
    }

    public function testHasByAlias()
    {
        $model = $this->getMock('\Moss\Storage\Model\ModelInterface');
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('foofoo'));

        $bag = new ModelBag();
        $bag->set($model, 'foofoo');
        $this->assertTrue($bag->has('foofoo'));
    }
}
