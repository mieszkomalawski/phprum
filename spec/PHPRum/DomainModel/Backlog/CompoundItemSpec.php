<?php

namespace spec\PHPRum\DomainModel\Backlog;

use BacklogBundle\Entity\User;
use PHPRum\DomainModel\Backlog\Exception\InvalidActionException;
use PHPRum\DomainModel\Backlog\Exception\InvalidEstimate;
use PHPRum\DomainModel\Backlog\CompoundItem;
use PHPRum\DomainModel\Backlog\Item;
use PHPRum\DomainModel\Backlog\ItemStatus;
use PHPRum\DomainModel\Backlog\Sprint;
use PHPRum\DomainModel\Backlog\SubItem;
use PHPRum\EventDispatcher;
use PhpSpec\ObjectBehavior;

class CompoundItemSpec extends ObjectBehavior
{
    function let(User $user, EventDispatcher $eventDispatcher)
    {
        $this->beConstructedWith('test-item', $user, $eventDispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CompoundItem::class);
    }

    function it_can_be_estimated()
    {
        $this->setEstimate(3);
        $this->getEstimate()->shouldBeLike(3);
    }

    function it_cant_be_estimated()
    {
        $this->shouldThrow(InvalidEstimate::class)->duringSetEstimate(4);
    }

    function it_can_remove_estimate()
    {
        $this->setEstimate(3);
        $this->getEstimate()->shouldBeLike(3);
        $this->setEstimate(0);
        $this->getEstimate()->shouldBeEqualTo(null);
    }

    function it_can_remove_priority()
    {
        $this->setPriority(10);
        $this->getPriority()->shouldBeLike(10);
        $this->setPriority(0);
        $this->getPriority()->shouldBeEqualTo(null);
    }

    function it_can_change_status()
    {
        $this->setStatus(ItemStatus::NEW());
        $this->setStatus(ItemStatus::IN_PROGRESS());
        $this->setStatus(ItemStatus::DONE());
    }

    function it_cant_create_sub_item_if_done()
    {
        $this->setStatus(ItemStatus::DONE());
        $this->shouldThrow(\Exception::class)->duringCreateSubItem('sub-item-name');
    }

    function it_can_create_sub_item()
    {
        $this->setStatus(ItemStatus::NEW());
        /** @var SubItem $subItem */
        $subItem = $this
            ->createSubItem('new-sub-item')
            ->shouldBeAnInstanceOf(SubItem::class);

        $this->getSubItems()->shouldBe([$subItem]);
    }

    function it_cant_finish_item_that_has_unfinished_sub_item()
    {
        $this->createSubItem('sub1')->setStatus(ItemStatus::DONE());
        $this->createSubItem('sub2')->setStatus(ItemStatus::IN_PROGRESS());

        $this->shouldThrow(InvalidActionException::class)->duringSetStatus(ItemStatus::DONE());
    }

    function it_can_be_added_to_sprint(Sprint $sprint)
    {
        $sub1 = $this->createSubItem('sub1');
        $sub2 = $this->createSubItem('sub2');

        $this->addToSprint($sprint);

        $sub1->getSprint()->shouldBe($sprint);
        $sub2->getSprint()->shouldBe($sprint);
    }

    function it_can_be_removed_from_sprint(Sprint $sprint)
    {
        $sub1 = $this->createSubItem('sub1');
        $sub2 = $this->createSubItem('sub2');

        $this->addToSprint($sprint);

        $this->removeFromSprint();

        $sub1->getSprint()->shouldBe(null);
        $sub2->getSprint()->shouldBe(null);
    }

    public function it_cannot_add_sub_item_to_done_item()
    {
        $this->done();
        $this->shouldThrow(InvalidActionException::class)->duringCreateSubItem('sub1');
    }

    public function it_can_be_blocked_by(CompoundItem $item)
    {
        $item->addBlockedBy($this)->shouldBeCalled();
        $this->addBlockedBy($item);
    }

    public function it_cannot_be_finshed_when_is_blocked_by(CompoundItem $item)
    {
        $item->addBlockedBy($this)->shouldBeCalled();
        $item->isDone()->willReturn(false);
        $this->addBlockedBy($item);
        $this->shouldThrow(InvalidActionException::class)->duringSetStatus(ItemStatus::DONE());
    }

    public function it_can_be_started_when_is_blocked_by_done(CompoundItem $item)
    {
        $item->addBlockedBy($this)->shouldBeCalled();
        $item->isDone()->willReturn(true);
        $this->addBlockedBy($item);
        $this->setStatus(ItemStatus::IN_PROGRESS());
    }
}
