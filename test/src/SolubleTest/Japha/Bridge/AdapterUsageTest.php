<?php

namespace SolubleTest\Japha\Bridge;

use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Interfaces;
use Soluble\Japha\Bridge\Exception;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-04 at 16:47:42.
 */
class AdapterUsageTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var string
     */
    protected $servlet_address;

    /**
     *
     * @var Adapter
     */
    protected $adapter;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        \SolubleTestFactories::startJavaBridgeServer();

        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();

        $this->adapter = new Adapter(array(
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ));

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testGetDriver()
    {
        $driver = $this->adapter->getDriver();
        $this->assertInstanceOf('Soluble\Japha\Bridge\Driver\AbstractDriver', $driver);
    }

    public function testJavaBigInt()
    {
        $ba = $this->adapter;
        $bigint1 = $ba->java('java.math.BigInteger', 10);
        $bigint2 = $ba->java('java.math.BigInteger', 1234567890);
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $bigint1);
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $bigint2);
        $bigint1 = $bigint1->add($bigint2);

        $this->assertEquals("1234567900", (string) $bigint1);
        $this->assertEquals(1234567900, $bigint1->intValue());
    }

    public function testJavaStrings()
    {
        $ba = $this->adapter;

        // ascii
        $string = $ba->java('java.lang.String', "Am I the only one ?");
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $string);
        $this->assertEquals('Am I the only one ?', $string);
        $this->assertNotEquals('Am I the only one', $string);

        // unicode - utf8
        $string = $ba->java('java.lang.String', "保障球迷權益");
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $string);
        $this->assertEquals('保障球迷權益', $string);
        $this->assertNotEquals('保障球迷', $string);
    }

    public function testJavaHashMap()
    {
        $ba = $this->adapter;
        $hash = $ba->java('java.util.HashMap', array('my_key' => 'my_value'));
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $hash);
        $this->assertEquals('my_value', $hash->get('my_key'));
        $hash->put('new_key', 'oooo');
        $this->assertEquals('oooo', $hash->get('new_key'));
        $hash->put('new_key', 'pppp');
        $this->assertEquals('pppp', $hash->get('new_key'));

        $this->assertEquals(4, $hash->get('new_key')->length());

        $hash->put('key', $ba->java('java.lang.String', "保障球迷權益"));
        $this->assertEquals('保障球迷權益', $hash->get('key'));
        $this->assertEquals(6, $hash->get('key')->length());
    }

    public function testJavaClass()
    {
        $ba = $this->adapter;
        $cls = $ba->javaClass('java.lang.Class');
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaClass', $cls);

    }

    public function testIsInstanceOf()
    {
        $ba = $this->adapter;

        $system = $ba->javaClass('java.lang.System');
        $system = $ba->javaClass('java.lang.System');
        $string = $ba->java('java.lang.String', 'Hello');
        $bigint = $ba->java('java.math.BigInteger', 1234567890123);
        $hash = $ba->java('java.util.HashMap', array());

        $this->assertFalse($ba->isInstanceOf($system, $string));
        $this->assertFalse($ba->isInstanceOf($hash, $string));
        $this->assertTrue($ba->isInstanceOf($string, 'java.lang.String'));
        $this->assertFalse($ba->isInstanceOf($string, 'java.util.HashMap'));
        $this->assertTrue($ba->isInstanceOf($hash, 'java.util.HashMap'));
        $this->assertTrue($ba->isInstanceOf($bigint, 'java.math.BigInteger'));
        $this->assertTrue($ba->isInstanceOf($bigint, 'java.lang.Object'));
        $this->assertTrue($ba->isInstanceOf($hash, 'java.lang.Object'));

        $this->assertFalse($ba->isInstanceOf($system, 'java.lang.System'));
    }

    public function testCommonExceptions()
    {
        $ba = $this->adapter;

        try {
            $string = $ba->java('java.lang.String', "Hello world");
            $string->anInvalidMethod();
            $this->assertFalse(true, "This code cannot be reached");
        } catch (Exception\NoSuchMethodException $e) {
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertFalse(true, "This code cannot be reached");
        }


        // Class not found
        try {
            $string = $ba->java('java.INVALID.String', "Hello world");
            $this->assertFalse(true, "This code cannot be reached");
        } catch (Exception\ClassNotFoundException $e) {
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertFalse(true, "This code cannot be reached");
        }

        try {
            $string = $ba->java("java.Invalid.String", "Hello world");
        } catch (Exception\JavaException $e) {
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertFalse(true, "This code cannot be reached");
        }
    }

    public function testDate()
    {

        $ba = $this->adapter;

        // Step 1: Check with system java timezone

        $pattern = "yyyy-MM-dd HH:mm";
        $formatter = $ba->java("java.text.SimpleDateFormat", $pattern);
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone("UTC");
        $formatter->setTimeZone($tz);

        $first = $formatter->format($ba->java("java.util.Date", 0));
        $this->assertEquals('1970-01-01 00:00', $first);

        $systemJavaTz = (string) $formatter->getTimeZone()->getId();


        $dateTime = new \DateTime(null, new \DateTimeZone($systemJavaTz));

        $now = $formatter->format($ba->java("java.util.Date"));
        $this->assertEquals($dateTime->format('Y-m-d H:i'), $now);


        // Step 2: Check with system php timezone

        $pattern = "yyyy-MM-dd HH:mm";
        $formatter = $ba->java("java.text.SimpleDateFormat", $pattern);
        $systemPhpTz  = date_default_timezone_get();
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone($systemPhpTz);
        $formatter->setTimeZone($tz);

        $dateTime = new \DateTime(null);

        $now = $formatter->format($ba->java("java.util.Date"));
        $this->assertEquals($dateTime->format('Y-m-d H:i'), $now);

        // Step 3: Different Timezones (europe/london and europe/paris -> 1 hour difference)

        $pattern = "yyyy-MM-dd HH:mm:ss";

        $formatter = $ba->java("java.text.SimpleDateFormat", $pattern);

        $phpTz = new \DateTimeZone("Europe/Paris");

        $reference_date = "2012-11-07 12:52:23";
        $phpDate  = \DateTime::createFromFormat("Y-m-d H:i:s", $reference_date, $phpTz);

        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone("Europe/Paris"));
        $date = $formatter->parse($reference_date);
        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone("Europe/London"));
        $javaDate = (string) $formatter->format($date);
        $this->assertNotEquals($phpDate->format('Y-m-d H:i:s'), $javaDate);
        $this->assertEquals($reference_date, $phpDate->format('Y-m-d H:i:s'));

        $phpDate->sub(new \DateInterval('PT1H'));
        $this->assertEquals($phpDate->format('Y-m-d H:i:s'), $javaDate);

    }

    public function testIterator()
    {

        $ba = $this->adapter;

        $system = $ba->javaClass('java.lang.System');
        $properties = $system->getProperties();

        foreach ($properties as $key => $value) {
            $this->assertInternalType('string', $key);
            $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $value);

            if ($key == 'java.version') {
                $this->assertStringStartsWith('1', $value->__toString());
            }
        }

        $iterator = $properties->getIterator();
        $this->assertInstanceOf('Soluble\Japha\Bridge\Driver\Pjb62\ObjectIterator', $iterator);
        $this->assertInstanceOf('Iterator', $iterator);

        foreach ($iterator as $key => $value) {
            $this->assertInternalType('string', $key);
            $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaObject', $value);

            if ($key == 'java.version') {
                $this->assertStringStartsWith('1', $value->__toString());
            }
        }
    }


    public function testGetSystem()
    {

        $system = $this->adapter->getSystem();
        $this->assertInstanceOf('Soluble\Japha\Bridge\Adapter\System', $system);

    }
}
