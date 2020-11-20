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

# Example tasks

```
wp option get boldgrid_backup_tasks
array (
	0 => array (
		'id'           => '1597861098-2e90c6',
		'type'         => 'backup',
		'created_at'   => 1597861098,
		'started_at'   => 1597861098,
		'completed_at' => 1597861109,
		'status'       => 'done',
		'data'         => array (),
	),
	1 => array (
		'id'           => '1597861521-1b2848',
		'type'         => 'backup',
		'created_at'   => 1597861521,
		'started_at'   => 1597861522,
		'completed_at' => 1597861527,
		'status'       => 'done',
		'data'         => array(),
	),
	2 => array (
		'id'           => '1598616953-986059',
		'type'         => 'backup',
		'created_at'   => 1598616953,
		'started_at'   => 1598616954,
		'completed_at' => 1598616959,
		'status'       => 'done',
		'data'         => array(),
	),
	3 => array (
		'id'           => '1598617517-e6f0a3',
		'type'         => 'backup',
		'created_at'   => 1598617517,
		'started_at'   => 1598617518,
		'completed_at' => 1598617523,
		'status'       => 'done',
		'data'         => array(),
	),
	4 => array (
		'id'           => '1598619019-8d3da6',
		'type'         => 'backup',
		'created_at'   => 1598619019,
		'started_at'   => 1598619020,
		'completed_at' => NULL,
		'status'       => 'in_progress',
		'data'         => array(),
	),
	5 => array (
		'id'           => '1598619948-985ee2',
		'type'         => 'restore',
		'created_at'   => '2020-08-28T13:05:48+00:00',
		'started_at'   => 1598619949,
		'completed_at' => 1598619951,
		'status'       => 'done',
		'data'         => array (
			'backup_id' => '4',
		),
	),
)
```
