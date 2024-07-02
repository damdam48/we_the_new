<?php

namespace App\Tests\Traits;

trait Testtrait
{
    public function assertHasErrors(mixed $entity, int $number = 0 ): void
    {
        // on initialization du noyau symfony
        self::bootKernel();

        // on test l'entité avec validator
        $errors = self::getContainer()->get('validator')->validate($entity);

        // on instancie un tableau vide pour stocker les erreurs
        $messageErrors = [];

        // on boucles sur les erreurs
        foreach ($errors as $error) {
            // on récupère le message d'erreur
            $messageErrors[] = $error->getPropertyPath() . " => " . $error->getMessage();
        }

        $this->assertCount($number, $errors, implode(', ', $messageErrors));
    }
}