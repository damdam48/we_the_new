<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;

class SecurityControllerTest extends WebTestCase
{

    private ?KernelBrowser $client = null;

    private ?ORMDatabaseTool $databaseTool = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        $this->databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->databaseTool->loadAliceFixture([
            \dirname(__DIR__) . '/Fixtures/UserFixtures.yaml',
        ]);
    }

    private function getAdminUser(): User
    {
        return self::getContainer()->get(UserRepository::class)
        ->findOneBy(['email' => 'admin@test.com']);
    }

    private function getEditorUser(): User
    {
        return self::getContainer()->get(UserRepository::class)
        ->findOneBy(['email' => 'editor@test.com']);
    }



    public function testResponseLoginPage(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(200);
    }


    public function testLoginFormWithGoodCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@test.com',
            '_password' => 'Test1234!',
        ]);
        
        $this->client->submit($form);

        $this->assertResponseRedirects('/');
    }

    public function testLoginFormWithBadCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@test.com',
            '_password' => 'Test',
        ]);
        
        $this->client->submit($form);

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert.alert-danger', 'Identifiants invalides.');
    }

    public function testAdminUserPageWithNoConnected(): void
    {
        $this->client->request('GET', '/admin/users');

        $this->assertResponseRedirects('/login');

    }



    public function testAdminUserPageWithAdminUser(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $this->client->request('Get', '/admin/users');

        $this->assertResponseStatusCodeSame(200);

    }


    public function testAdminUserPageWithEditorUser(): void
    {
        $this->client->loginUser($this->getEditorUser());

        $this->client->request('Get', '/admin/users');

        $this->assertResponseStatusCodeSame(403);

    }

    // // test unitaire pour la page register
    // public function testPageRegister(): void
    // {
    //     $crawler = $this->client->request('GET', '/register');

    //     $form = $crawler->selectButton('Se connecter')->form([
    //         'user{firstName}' => 'test',
    //         'user{lastName}' => 'test',
    //         'user[email]' => 'Test@test.com',
    //         'user[password][first]' => 'Test1234!',
    //         'user[password][second]' => 'Test1234!',
    //     ]);

    //     $this->client->submit($form);

    //     $this->assertResponseRedirects('/login');

    // }






    

    public function tearDown(): void
    {
        parent::tearDown();

        $this->databaseTool = null;
        $this->client = null;
    }


}