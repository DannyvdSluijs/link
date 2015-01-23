<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.01.15 - 18:10
 */

namespace ProcessorProxyTest\Model;

use Ginger\Message\LogMessage;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\Command\StartSubProcess;
use Ginger\Processor\Event\SubProcessFinished;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use ProcessorProxy\Model\MessageLogEntry;
use ProcessorProxy\Model\MessageStatus;
use ProcessorProxyTest\TestCase;
use ProcessorProxyTest\DataType\TestUser;

/**
 * Class MessageLogEntryTest
 *
 * @package ProcessorProxyTest\Model
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class MessageLogEntryTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessages
     */
    public function it_can_be_created_from_any_ginger_message_even_if_wrapped_in_a_service_bus_message($message, $expectedLogData)
    {
        $logEntry = MessageLogEntry::logMessage($message);

        $logEntry = $logEntry->toArray();

        $this->assertTrue(isset($logEntry['logged_at']));

        $loggedAt = \DateTime::createFromFormat('Y-m-d\TH:i:s.uO', $logEntry['logged_at']);

        $this->assertEquals(date('Y-m-d'), $loggedAt->format('Y-m-d'));

        unset($logEntry['logged_at']);

        $this->assertEquals($logEntry, $expectedLogData);
    }

    public function provideMessages()
    {
        $localTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $remoteTaskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::fromString("remote-system"), ProcessId::generate()), 1);

        $wfMessageWithoutTaskListPosition = WorkflowMessage::collectDataOf(TestUser::prototype());
        $expectedDataWfMessageWithoutTaskListPosition = [
            'message_id' => $wfMessageWithoutTaskListPosition->uuid()->toString(),
            'message_name' => $wfMessageWithoutTaskListPosition->messageName(),
            'version' => $wfMessageWithoutTaskListPosition->version(),
            'task_list_position' => null,
            'process_id' => null,
            'status' => MessageStatus::PENDING,
            'failure_msg' => null
        ];

        $wfMessageWithTaskListPosition = WorkflowMessage::collectDataOf(TestUser::prototype());
        $wfMessageWithTaskListPosition->connectToProcessTask($localTaskListPosition);
        $expectedDataWfMessageWithTaskListPosition = [
            'message_id' => $wfMessageWithTaskListPosition->uuid()->toString(),
            'message_name' => $wfMessageWithTaskListPosition->messageName(),
            'version' => $wfMessageWithTaskListPosition->version(),
            'task_list_position' => $wfMessageWithTaskListPosition->processTaskListPosition()->toString(),
            'process_id' => $wfMessageWithTaskListPosition->processTaskListPosition()->taskListId()->processId()->toString(),
            'status' => MessageStatus::PENDING,
            'failure_msg' => null
        ];

        $logMessage = LogMessage::logDebugMsg("Log message", $localTaskListPosition);
        $expectedDataLogMessage = [
            'message_id' => $logMessage->uuid()->toString(),
            'message_name' => $logMessage->messageName(),
            'version' => 1,
            'task_list_position' => $logMessage->processTaskListPosition()->toString(),
            'process_id' => $logMessage->processTaskListPosition()->taskListId()->processId()->toString(),
            'status' => MessageStatus::PENDING,
            'failure_msg' => null
        ];

        $startSubProcess = StartSubProcess::at($localTaskListPosition, ["process_type" => "faked"], false, "remote-system");
        $expectedDataStartSubProcess = [
            'message_id' => $startSubProcess->uuid()->toString(),
            'message_name' => $startSubProcess->messageName(),
            'version' => $startSubProcess->version(),
            'task_list_position' => $startSubProcess->parentTaskListPosition()->toString(),
            'process_id' => $startSubProcess->parentTaskListPosition()->taskListId()->processId()->toString(),
            'status' => MessageStatus::PENDING,
            'failure_msg' => null
        ];

        $logMessageSubProcess = LogMessage::logErrorMsg("Faked error", $remoteTaskListPosition);
        $subProcessFinished = SubProcessFinished::record(
            NodeName::fromString("remote-system"),
            $remoteTaskListPosition->taskListId()->processId(),
            false,
            $logMessageSubProcess,
            $localTaskListPosition
        );
        $expectedDataSubProcessFinished = [
            'message_id' => $subProcessFinished->uuid()->toString(),
            'message_name' => $subProcessFinished->messageName(),
            'version' => $subProcessFinished->version(),
            'task_list_position' => $logMessageSubProcess->processTaskListPosition()->toString(),
            'process_id' => $logMessageSubProcess->processTaskListPosition()->taskListId()->processId()->toString(),
            'status' => MessageStatus::PENDING,
            'failure_msg' => null
        ];

        return [
            [
                $wfMessageWithoutTaskListPosition,
                $expectedDataWfMessageWithoutTaskListPosition
            ],
            [
                $wfMessageWithoutTaskListPosition->toServiceBusMessage(),
                $expectedDataWfMessageWithoutTaskListPosition
            ],
            [
                $wfMessageWithTaskListPosition,
                $expectedDataWfMessageWithTaskListPosition
            ],
            [
                $wfMessageWithTaskListPosition->toServiceBusMessage(),
                $expectedDataWfMessageWithTaskListPosition
            ],
            [
                $logMessage,
                $expectedDataLogMessage
            ],
            [
                $logMessage->toServiceBusMessage(),
                $expectedDataLogMessage
            ],
            [
                $startSubProcess,
                $expectedDataStartSubProcess
            ],
            [
                $startSubProcess->toServiceBusMessage(),
                $expectedDataStartSubProcess
            ],
            [
                $subProcessFinished,
                $expectedDataSubProcessFinished
            ],
            [
                $subProcessFinished->toServiceBusMessage(),
                $expectedDataSubProcessFinished
            ],
        ];
    }

    /**
     * @test
     */
    public function it_allows_to_assign_a_process_id_when_a_start_message_was_logged()
    {
        $wfMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $messageLogEntry = MessageLogEntry::logMessage($wfMessage);

        $this->assertTrue($messageLogEntry->wasAStartMessageLogged());
        $this->assertTrue($messageLogEntry->status()->isPending());

        $messageLogEntry->assignIdOfStartedProcess(ProcessId::generate());

        $this->assertTrue($messageLogEntry->wasAStartMessageLogged());
        $this->assertFalse($messageLogEntry->status()->isSucceed());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_does_not_allow_to_set_a_process_id_when_logged_message_was_not_a_start_message()
    {
        $wfMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage->connectToProcessTask($taskListPosition);

        $messageLogEntry = MessageLogEntry::logMessage($wfMessage);

        $messageLogEntry->assignIdOfStartedProcess(ProcessId::generate());
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_succeed()
    {
        $wfMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage->connectToProcessTask($taskListPosition);

        $messageLogEntry = MessageLogEntry::logMessage($wfMessage);

        $this->assertFalse($messageLogEntry->status()->isSucceed());

        $messageLogEntry->markAsSucceed();

        $this->assertTrue($messageLogEntry->status()->isSucceed());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_can_not_be_marked_as_succeed_if_start_message_was_logged_but_it_was_no_process_id_assigned()
    {
        $wfMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $messageLogEntry = MessageLogEntry::logMessage($wfMessage);

        $messageLogEntry->markAsSucceed();
    }

    /**
     * @test
     */
    public function it_can_be_marked_as_failed()
    {
        $wfMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $messageLogEntry = MessageLogEntry::logMessage($wfMessage);

        $this->assertFalse($messageLogEntry->status()->isFailed());

        $messageLogEntry->markAsFailed("Starting process failed");

        $this->assertTrue($messageLogEntry->status()->isFailed());
    }

    /**
     * @test
     * @dataProvider provideEntries
     */
    public function it_can_be_converted_to_array_and_back(MessageLogEntry $entry)
    {
        $entryData = $entry->toArray();

        $copiedEntry = MessageLogEntry::fromArray($entryData);

        $this->assertTrue($entry->messageId()->equals($copiedEntry->messageId()));
        $this->assertEquals($entry->messageName(), $copiedEntry->messageName());
        $this->assertEquals($entry->version(), $copiedEntry->version());
        $this->assertEquals($entry->loggedAt()->format('Y-m-d\TH:i:s.uO'), $copiedEntry->loggedAt()->format('Y-m-d\TH:i:s.uO'));

        if (is_null($entry->taskListPosition())) {
            $this->assertNull($copiedEntry->taskListPosition());
        } else {
            $this->assertTrue($entry->taskListPosition()->equals($copiedEntry->taskListPosition()));
        }

        if (is_null($entry->processId())) {
            $this->assertNull($copiedEntry->processId());
        } else {
            $this->assertTrue($entry->processId()->equals($copiedEntry->processId()));
        }

        $this->assertEquals($entry->status()->toString(), $copiedEntry->status()->toString());
        $this->assertEquals($entry->status()->failureMsg(), $copiedEntry->status()->failureMsg());
    }

    /**
     * @return array
     */
    public function provideEntries()
    {
        $wfStartMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $startMessageLogEntry = MessageLogEntry::logMessage($wfStartMessage);

        $startMessageLogEntryWithAssignedProcessId = MessageLogEntry::logMessage($wfStartMessage);

        $startMessageLogEntryWithAssignedProcessId->assignIdOfStartedProcess(ProcessId::generate());

        $startMessageLogEntryWithAssignedProcessIdSucceed = MessageLogEntry::logMessage($wfStartMessage);

        $startMessageLogEntryWithAssignedProcessIdSucceed->assignIdOfStartedProcess(ProcessId::generate());

        $startMessageLogEntryWithAssignedProcessIdFailed = MessageLogEntry::logMessage($wfStartMessage);

        $startMessageLogEntryWithAssignedProcessIdFailed->markAsFailed("Starting process failed");

        $wfMessage = WorkflowMessage::collectDataOf(TestUser::prototype());

        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage->connectToProcessTask($taskListPosition);

        $normalMessageLogEntryPending = MessageLogEntry::logMessage($wfMessage);

        $normalMessageLogEntrySucceed = MessageLogEntry::logMessage($wfMessage);

        $normalMessageLogEntrySucceed->markAsSucceed();

        $normalMessageLogEntryFailed = MessageLogEntry::logMessage($wfMessage);

        $normalMessageLogEntryFailed->markAsFailed("Processing failed");

        return [
            [
                $startMessageLogEntry,
            ],
            [
                $startMessageLogEntryWithAssignedProcessId,
            ],
            [
                $startMessageLogEntryWithAssignedProcessIdSucceed,
            ],
            [
                $startMessageLogEntryWithAssignedProcessIdFailed,
            ],
            [
                $normalMessageLogEntryPending,
            ],
            [
                $normalMessageLogEntrySucceed,
            ],
            [
                $normalMessageLogEntryFailed,
            ],
        ];
    }
}
 