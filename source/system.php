<?php

function activeUsersEndStart(DateTime $dateTime): void
{
    $mongoDB = RIOMongoDatabase::getInstance();
    $workDaysCollection = $mongoDB->getWorkDaysCollection();
    /** @var \MongoDB\Model\BSONDocument[] $users */
    $users = $mongoDB->getUsers();
    $usersActive = $mongoDB->getActiveUsers();

    foreach ($usersActive as $userActive) {

    }

    foreach ($users as $user) {
        $workDaysCollection->findOne(["date" => $dateTime->format("d.m.Y"), "session_username" => $user->offsetGet("session_username")]);
    }
}