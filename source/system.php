<?php

function activeUsersEndStart(DateTime $dateTime): void
{
    $mongoDB = RIOMongoDatabase::getInstance();
    $year = $dateTime->format("Y");
    $usersInactive = $mongoDB->getInactiveUsers();
    $usersActive = $mongoDB->getActiveUsers();

    foreach ($usersActive as $userActive) {
        $workDaysCollection = $mongoDB->getWorkDaysCollectionByYearUser($year, $userActive->offsetGet("sessionUsername"));
        $object = $workDaysCollection->findOne(["date" => $dateTime->format("d.m.Y")]);
    }

    foreach ($usersInactive as $userInactive) {
        $workDaysCollection = $mongoDB->getWorkDaysCollectionByYearUser($year, $userInactive->offsetGet("sessionUsername"));
        $object = $workDaysCollection->findOne(["date" => $dateTime->format("d.m.Y")]);
    }
}