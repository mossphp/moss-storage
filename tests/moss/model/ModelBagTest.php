<?php
/**
 * Created by PhpStorm.
 * User: Michal
 * Date: 21.02.14
 * Time: 14:17
 */

namespace moss\storage\model;

use moss\storage\model\definition\field\Field;

class ModelBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nameProvider
     */
    public function testGetSet($name, $expected)
    {
        $bag = new ModelBag();
        $bag->set(new Model('\stdClass', 'table', array(new Field('id'))), 'std');
        $bag->set(new Model('\splFileObject', 'table', array(new Field('id'))), 'spl');
        $bag->set(new Model('\foo\bar\Yada', 'table', array(new Field('id'))), 'yada');

        $model = $bag->get($name);
        $this->assertEquals($model->entity(), $expected);
    }

    public function nameProvider()
    {
        return array(
            array('\stdClass', 'stdClass'),
            array('\splFileObject', 'splFileObject'),
            array('\foo\bar\Yada', 'foo\bar\Yada'),

            array('std', 'stdClass'),
            array('spl', 'splFileObject'),
            array('yada', 'foo\bar\Yada')
        );
    }

}
 