<?php

function activeUsersEndStart(DateTime $dateTime): void
{
    $mongoDB = RIOMongoDatabase::getInstance();
    $workDaysCollection = $mongoDB->getWorkDaysCollection();
    $usersInactive = $mongoDB->getInactiveUsers();
    $usersActive = $mongoDB->getActiveUsers();

    foreach ($usersActive as $userActive) {

    }

    foreach ($usersInactive as $userInactive) {
        $workDaysCollection->findOne(["date" => $dateTime->format("d.m.Y"), "session_username" => $userInactive->offsetGet("session_username")]);
    }
}