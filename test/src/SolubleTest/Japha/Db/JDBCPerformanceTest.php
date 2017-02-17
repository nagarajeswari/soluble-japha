<?php

namespace SolubleTest\Japha\Db;

use Soluble\Japha\Bridge\Adapter as Adapter;
use Soluble\Japha\Db\DriverManager;

class JDBCPerformanceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $servlet_address;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var DriverManager
     */
    protected $driverManager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $s = $_SERVER;
        if (isset($s['JAPHA_ENABLE_JDBC_TESTS']) && $s['JAPHA_ENABLE_JDBC_TESTS'] == true) {
            $this->markTestSkipped(
                'Skipping JDBC mysql performance tests, enable option in phpunit.xml'
            );
        }

        \SolubleTestFactories::startJavaBridgeServer();
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();

        $this->adapter = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ]);
        $this->driverManager = new DriverManager($this->adapter);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testStatementWithGetValuesOptimization()
    {
        $ba = $this->adapter;
        $dsn = $this->getPHPUnitJdbcDSN(); // "jdbc:mysql://$host/$db?user=$user&password=$password"
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $e) {
            $this->assertFalse(true, 'Cannot connect: ' . $e->getMessage());
        }

        $stmt = $conn->createStatement();
        $rs = $stmt->executeQuery('select * from product_category_translation limit 1000');

        $list = $ba->java('java.util.LinkedList');  // 1 round trip (ArrayList is possible)

        while ($rs->next()) {
            $list->add($rs->getString('title')); // Data is set in the JVM only
        }

        $titles = (array) $ba->getDriver()->values($list); // 1 round trip

        $this->assertContains('Jack', $titles);

        if (!$ba->isNull($rs)) {
            $rs->close();
        }
        if (!$ba->isNull($stmt)) {
            $stmt->close();
        }

        $conn->close();
    }

    public function testStatementWithMapAndGetValuesOptimization()
    {
        $ba = $this->adapter;
        $dsn = $this->getPHPUnitJdbcDSN(); // "jdbc:mysql://$host/$db?user=$user&password=$password"
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $e) {
            $this->assertFalse(true, 'Cannot connect: ' . $e->getMessage());
        }

        $stmt = $conn->createStatement();
        $rs = $stmt->executeQuery('select * from product_category limit 1000');

        $list = $ba->java('java.util.HashMap');  // 1 round trip

        while ($rs->next()) {
            $list->put($rs->getString('reference'), $rs->getString('title')); // Data is set in the JVM only
        }

        $titles = (array) $ba->getDriver()->values($list); // 1 round trip

        $this->assertContains('Accessoires', $titles);
        $this->assertArrayHasKey('PIAC', $titles);
        $this->assertEquals('Accessoires', $titles['PIAC']);

        if (!$ba->isNull($rs)) {
            $rs->close();
        }
        if (!$ba->isNull($stmt)) {
            $stmt->close();
        }

        $conn->close();
    }

    protected function getPHPUnitJdbcDSN()
    {
        $config = \SolubleTestFactories::getDatabaseConfig();
        $host = $config['hostname'];
        $db = $config['database'];
        $user = $config['username'];
        $password = $config['password'];
        $dsn = "jdbc:mysql://$host/$db?user=$user&password=$password";

        return $dsn;
    }
}
