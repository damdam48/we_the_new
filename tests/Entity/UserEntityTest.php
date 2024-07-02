<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\TestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;

class UserEntityTest extends KernelTestCase
{
    use TestTrait;

    protected ?ORMDatabaseTool $databaseTool = Null;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function testRepositotryCount(): void
    {
        $this->databaseTool->loadAliceFixture([
            \dirname(__DIR__) . '/Fixtures/UserFixtures.yaml',
        ]);

        $userRepo = self::getContainer()->get(UserRepository::class);

        $users = $userRepo->findAll();

        $this->assertCount(12, $users);
    }

    private function getEntity(): User
    {
        return (new User)
            ->setEmail('test@test.com')
            ->setFirstName('test')
            ->setLastName('test')
            ->setPassword('test');
    }

    public function testValideEntity(): void
    {
        $this->assertHasErrors($this->getEntity());
    }

    // public function testNonUniqueEmail(): void
    // {
    //     $user = $this->getEntity()
    //         ->setEmail('admin@test.com');

    //     $this->assertHasErrors($user, 1);
    // }


    // public function testMaxLengthEmail()
    // {
    //     $user = $this->getEntity()
    //         ->setEmail('a' . str_repeat('a', 180) . '@test.com');

    //     $this->assertHasErrors($user, 1);
    // }


    /**
     * @dataProvider provideEmail
     *
     * @param string $email
     * @return void
     */
    public function testInvalideEmail(string $email, int $number): void
    {
        $user = $this->getEntity()
            ->setEmail($email);
        $this->assertHasErrors($user, $number);
    }

    public function provideEmail(): array
    {
        return [
            'non_unique' => [
                'email' => 'admin@test.com',
                'number' => 1,
            ],
            'max_length' => [
                'email' => 'a' . str_repeat('a', 180) . '@test.com',
                'number' => 1,
            ],
            'empty' => [
                'email' => '',
                'number' => 1,
            ],
            'invalid' => [
                'email' => 'test.com',
                'number' => 1,
            ],
        ];
    }




    /**
     * @dataProvider provideFirstName
     *
     * @param string $name
     * @return void
     */
    public function testInvalideFirstName(string $Name, int $number): void
    {
        $user = $this->getEntity()
            ->setFirstName($Name);
        $this->assertHasErrors($user, $number);
    }

    public function provideFirstName(): array
    {
        return [
            'max_length' => [
                'firstName' => 'a' . str_repeat('a', 255) . '',
                'number' => 1,
            ],
            'empty' => [
                'firstName' => '',
                'number' => 1,
            ],
        ];
    }




    /**
     * @dataProvider provideFirstName
     *
     * @param string $lastName
     * @return void
     */
    public function testInvalideLastName(string $lastName, int $number): void
    {
        $user = $this->getEntity()
            ->setLastName($lastName);
        $this->assertHasErrors($user, $number);
    }

    public function provideLastName(): array
    {
        return [
            'max_length' => [
                'lastName' => 'a' . str_repeat('a', 255) . '',
                'number' => 1,
            ],
            'empty' => [
                'lastName' => '',
                'number' => 1,
            ],
        ];
    }




    /**
     * @dataProvider provideFirstName
     *
     * @param string $phone
     * @return void
     */
    public function testInvalidePhone(string $phone, int $number): void
    {
        $user = $this->getEntity()
            ->setFirstName($phone);
        $this->assertHasErrors($user, $number);
    }

    public function providePhone(): array
    {
        return [
            'max_length' => [
                'phone' => 'a' . str_repeat('a', 10) . '',
                'number' => 1,
            ],
        ];
    }





    public function testFindPaginateOrderByDate(): void
    {
        $repo = self::getContainer()->get(UserRepository::class);
        $users = $repo->findPaginateOrderByDate(9, 1);

        $this->assertCount(9, $users);
    }



    public function testfindPaginateOrderByDateWithSearche(): void
    {
        $repo = self::getContainer()->get(UserRepository::class);
        $users = $repo->findPaginateOrderByDate(9, 1, 'admin');

        $this->assertCount(1, $users);
    }





    public function tearDown(): void
    {
        $this->databaseTool = null;
        parent::tearDown();
    }
}
