<?php

namespace App\Tests\Controller;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContactControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $contactRepository;
    private string $path = '/contact/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->contactRepository = $this->manager->getRepository(Contact::class);

        foreach ($this->contactRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Contact index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'contact[phoneNumber]' => 'Testing',
            'contact[firstName]' => 'Testing',
            'contact[lastName]' => 'Testing',
            'contact[email]' => 'Testing',
            'contact[contactGroups]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->contactRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Contact();
        $fixture->setPhoneNumber('My Title');
        $fixture->setFirstName('My Title');
        $fixture->setLastName('My Title');
        $fixture->setEmail('My Title');
        $fixture->setContactGroups('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Contact');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Contact();
        $fixture->setPhoneNumber('Value');
        $fixture->setFirstName('Value');
        $fixture->setLastName('Value');
        $fixture->setEmail('Value');
        $fixture->setContactGroups('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'contact[phoneNumber]' => 'Something New',
            'contact[firstName]' => 'Something New',
            'contact[lastName]' => 'Something New',
            'contact[email]' => 'Something New',
            'contact[contactGroups]' => 'Something New',
        ]);

        self::assertResponseRedirects('/contact/');

        $fixture = $this->contactRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getPhoneNumber());
        self::assertSame('Something New', $fixture[0]->getFirstName());
        self::assertSame('Something New', $fixture[0]->getLastName());
        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getContactGroups());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Contact();
        $fixture->setPhoneNumber('Value');
        $fixture->setFirstName('Value');
        $fixture->setLastName('Value');
        $fixture->setEmail('Value');
        $fixture->setContactGroups('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/contact/');
        self::assertSame(0, $this->contactRepository->count([]));
    }
}
