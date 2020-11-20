# Tasks vs. Jobs

## Jobs

Jobs is a "jobs queue". The queue is checked every 5 minutes, and if a job is found, it is ran. Each
job in the queue is really just a WordPress action/hook that is ran. When it is completed, it is marked
as complete. The next time the jobs queue is processed, it will run the next action in line.

For example, when a backup is made, 2 items may be add to the the jobs queue:
1. Upload to Google Drive
2. Upload to Amazon S3

Every 5 minutes the jobs queue is triggered. First, the Google Drive upload will be processed. When
it is done, it will be flagged as complete. 5 minutes later, when the queue is processed again, it will
find that the Amazon S3 job is next, and it will run the action for that.

## Tasks

A tasks is a thing to do, like "make a backup".

Here's an example of how a task works:

1. A Rest API call comes in to create a backup. We create a new backup task and then execute it.

```
$task = new Boldgrid_Backup_Admin_Task();
$task->init( [ 'type' => 'backup' ] );

// Trigger our backup.
$nopriv = new Boldgrid_Backup_Admin_Nopriv();
$nopriv->do_backup( [ 'task_id' => $task->get_id() ] );
```

This new task now has an id, status, a start time, etc.

2. Let's say that backup takes 5 minutes. Throughout that time, other Rest API calls can request the
status of that task. They'll continue to see "in progress" until the backup is complete and then the
task status will be "complete".

## The Difference

Jobs is a collection of jobs:

```
jobs queue
1. job
2. job
3. job
```

Theoretically, it could look like this (but it doesn't): 

```
jobs queue
1. task
2. task
3. task
```

Tasks were written ~2 or so years after the jobs queue was written, and are completely independent.
While a task and a job could be the same, the only similarities within Total Upkeep is that they represent
an action, and have things like a start time, a status, etc.

Jobs are a wordpress action/hook and belong to the jobs queue. Tasks are independent and could potentially
float around the system. Tasks are really just a tracking system for things to do.