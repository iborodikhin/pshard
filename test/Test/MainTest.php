<?php
namespace PShard\Test;

use PShard\Test as BaseTest;

/**
 * Test of main PShard functionality.
 */
class MainTest extends BaseTest
{
    /**
     * Test basic CRUD operations.
     *
     * @param string $login
     * @dataProvider provideTestCRUD
     */
    public function testCRUD($login)
    {
        $pdo = self::$pshard->getConnectionForKey($login);
        $this->assertInstanceOf('\PDO', $pdo);

        $stmt = $pdo->prepare('INSERT INTO user (login) VALUES (:login)');
        $this->assertInstanceOf('\PDOStatement', $stmt);

        $stmt->bindValue('login', $login);
        $this->assertTrue($stmt->execute());

        $stmt = $pdo->prepare('SELECT * FROM user WHERE login = :login');
        $this->assertInstanceOf('\PDOStatement', $stmt);

        $stmt->bindValue('login', $login);
        $this->assertTrue($stmt->execute());

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertInternalType('array', $row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('login', $row);
        $this->assertEquals($login, $row['login']);

        $stmt = $pdo->prepare('UPDATE user SET login = :login_new WHERE login = :login_old');
        $this->assertInstanceOf('\PDOStatement', $stmt);

        $stmt->bindValue('login_new', $login . '_new');
        $stmt->bindValue('login_old', $login);
        $this->assertTrue($stmt->execute());

        $stmt = $pdo->prepare('SELECT * FROM user WHERE login = :login');
        $this->assertInstanceOf('\PDOStatement', $stmt);

        $stmt->bindValue('login', $login . '_new');
        $this->assertTrue($stmt->execute());

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertInternalType('array', $row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('login', $row);
        $this->assertEquals($login . '_new', $row['login']);
        $this->assertNotEquals($login, $row['login']);

        $stmt = $pdo->prepare('DELETE FROM user WHERE login = :login');
        $this->assertInstanceOf('\PDOStatement', $stmt);

        $stmt->bindValue('login', $login . '_new');
        $this->assertTrue($stmt->execute());
    }

    /**
     * Data-provider for main test.
     *
     * @return array
     */
    public function provideTestCRUD()
    {
        $faker = \Faker\Factory::create('ru_RU');
        $faker->addProvider(new \Faker\Provider\Internet($faker));

        $result = [];

        for ($i = 0; $i < 100; $i++) {
            $result[] = [$faker->word];
        }

        return $result;
    }
}