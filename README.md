A very lightweight yet powerful PHP state machine
=================================================

Define your states, define your transitions and your callbacks: we do the rest.
The era of hard-coded states is over!

[![Build Status](https://travis-ci.org/winzou/state-machine.svg?branch=master)](https://travis-ci.org/winzou/state-machine)

Installation (via composer)
---------------

```js
{
    "require": {
        "james.rus52/state-machine": "~0.5"
    }
}
```

Usage
-----

### Configure a state machine graph

In order to use the state machine, you first need to define a graph. A graph is a definition of states, transitions and optionnally callbacks ; all attached on an object from your domain. Multiple graphes can be attached to the same object.

Let's define a graph called *myGraphA* for our `DomainObject` object:

```php
$config = [
                  'graph'  => 'Request',
                  'property_path' => 'Status',
                  'states' => [
                      RequestStatus::NEW    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE,
                              RequestAction::DELETE
                          ],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ],
                              RequestAction::DELETE => ['b_name' => 's_delete_request' ,'roles' => ['author'], 'onclick' => 'ConfirmDeleteRequest();' ]
                          ]
                      ],
                      RequestStatus::ANALYZE    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE,
                              RequestAction::ESCALATE,
                              RequestAction::DELETE,
                              RequestAction::SUSPEND,
                              RequestAction::UNSUSPEND,
                              ],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ],
                              RequestAction::ESCALATE => ['b_name' => 's_escalation', 'onclick' => 'ShowModalWindow(\'owner_model_window\',\'SubmitForm\',\'owner_model_window_form\');' ],
                              RequestAction::DELETE => ['b_name' => 's_delete_request' ,'roles' => ['superadmin'], 'onclick' => 'ConfirmDeleteRequest();' ],
                              RequestAction::SUSPEND => ['b_name' => 's_suspend' ],
                              RequestAction::UNSUSPEND => ['b_name' => 's_unsuspend'],
                          ],
                          'conditions' => [
                              RequestAction::SUSPEND => ['object', 'isSuspended', false ],
                              RequestAction::UNSUSPEND => ['object', 'isSuspended', true ],
                          ]
                      ],
                      RequestStatus::APPROVE    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE,
                              RequestAction::ESCALATE,
                              RequestAction::DELETE,
                              RequestAction::SUSPEND,
                              RequestAction::UNSUSPEND,
                              ],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ],
                              RequestAction::ESCALATE => ['b_name' => 's_escalation', 'onclick' => 'ShowModalWindow(\'owner_model_window\',\'SubmitForm\',\'owner_model_window_form\');' ],
                              RequestAction::DELETE => ['b_name' => 's_delete_request' ,'roles' => ['superadmin'], 'onclick' => 'ConfirmDeleteRequest();' ],
                              RequestAction::SUSPEND => ['b_name' => 's_suspend' ],
                              RequestAction::UNSUSPEND => ['b_name' => 's_unsuspend'],
                          ],
                          'conditions' => [
                              RequestAction::SUSPEND => ['object', 'isSuspended', false ],
                              RequestAction::UNSUSPEND => ['object', 'isSuspended', true ],
                          ]
                      ],
                      RequestStatus::APPROVED    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE,
                              RequestAction::ESCALATE,
                              RequestAction::DELETE,
                              RequestAction::SUSPEND,
                              RequestAction::UNSUSPEND,
                              ],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ],
                              RequestAction::ESCALATE => ['b_name' => 's_escalation', 'onclick' => 'ShowModalWindow(\'owner_model_window\',\'SubmitForm\',\'owner_model_window_form\');' ],
                              RequestAction::DELETE => ['b_name' => 's_delete_request' ,'roles' => ['superadmin'], 'onclick' => 'ConfirmDeleteRequest();' ],
                              RequestAction::SUSPEND => ['b_name' => 's_suspend' ],
                              RequestAction::UNSUSPEND => ['b_name' => 's_unsuspend'],
                          ],
                          'conditions' => [
                              RequestAction::SUSPEND => ['object', 'isSuspended', false ],
                              RequestAction::UNSUSPEND => ['object', 'isSuspended', true ],
                          ]
                      ],
                      RequestStatus::SENT    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ]
                          ]
                      ],
                      RequestStatus::DELIVERED    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE,
                              RequestAction::ESCALATE,
                              RequestAction::SUSPEND,
                              RequestAction::UNSUSPEND,
                          ],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ],
                              RequestAction::ESCALATE => ['b_name' => 's_escalation', 'onclick' => 'ShowModalWindow(\'owner_model_window\',\'SubmitForm\',\'owner_model_window_form\');' ],
                              RequestAction::SUSPEND => ['b_name' => 's_suspend' ],
                              RequestAction::UNSUSPEND => ['b_name' => 's_unsuspend'],
                          ],
                          'conditions' => [
                              RequestAction::SUSPEND => ['object', 'isSuspended', false ],
                              RequestAction::UNSUSPEND => ['object', 'isSuspended', true ],
                          ]
                      ],
                      RequestStatus::COMPLETED    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ]
                          ]
                      ],
                      RequestStatus::CANCELED    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ]
                          ]
                      ],
                      RequestStatus::REJECTED    => [
                          'actions' => [
                              RequestAction::COMMENT,
                              RequestAction::CLONE],
                          'properties' => [
                              RequestAction::COMMENT => ['b_name' => 's_comment' ],
                              RequestAction::CLONE => ['b_name' => 's_clone_request' ]
                          ]
                      ],
                  ],
                  'transitions' => [
                      RequestTransition::CANCEL  => [
                          'from' => [RequestStatus::NEW],
                          'to' => RequestStatus::CANCELED,
                          'properties' => ['b_name' => 's_to_cancel', 'css_class' => 'btn-outline-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::TO_ANALYZE => [
                          'from' => [RequestStatus::NEW],
                          'to' => RequestStatus::ANALYZE,
                          'properties' => ['b_name' => 's_to_analyze', 'css_class' => 'btn-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::REJECT  => [
                          'from' => [RequestStatus::ANALYZE],
                          'to' => RequestStatus::REJECTED,
                          'properties' => ['b_name' => 's_to_reject_admin', 'css_class' => 'btn-outline-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::BACK_TO_AUTHOR  => [
                          'from' => [RequestStatus::ANALYZE],
                          'to' => RequestStatus::NEW,
                          'properties' => ['b_name' => 's_return_to_author', 'css_class' => 'btn-outline-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::ANALYZE  => [
                          'from' => [RequestStatus::ANALYZE],
                          'to' => RequestStatus::APPROVE,
                          'properties' => ['b_name' => 's_aprove_my_resources', 'css_class' => 'btn-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::RETURN  => [
                          'from' => [RequestStatus::APPROVE],
                          'to' => RequestStatus::ANALYZE,
                          'properties' => ['b_name' => 's_to_returnanalyze', 'css_class' => 'btn-outline-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::REJECT  => [
                          'from' => [RequestStatus::APPROVE, RequestStatus::ANALYZE ],
                          'to' => RequestStatus::REJECTED,
                          'properties' => ['b_name' => 's_to_reject', 'css_class' => 'btn-outline-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::APPROVE  => [
                          'from' => [RequestStatus::APPROVE],
                          'to' => RequestStatus::APPROVED,
                          'properties' => ['b_name' => 's_to_approve', 'css_class' => 'btn-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::SEND  => [
                          'from' => [RequestStatus::APPROVED],
                          'to' => RequestStatus::SENT,
                          'properties' => ['b_name' => 's_to_partner', 'css_class' => 'btn-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::DELIVER  => [
                          'from' => [RequestStatus::SENT],
                          'to' => RequestStatus::DELIVERED,
                          'properties' => ['roles' => ['system']]],
                      RequestTransition::RESEND => [
                          'from' => [RequestStatus::DELIVERED],
                          'to' => RequestStatus::SENT,
                          'properties' => ['b_name' => 's_resend', 'css_class' => 'btn-primary', 'roles' => ['executor','superadmin']]
                      ],
                      RequestTransition::COMPLETE  => [
                          'from' => [RequestStatus::DELIVERED],
                          'to' => RequestStatus::COMPLETED, 'properties' => ['roles' => ['system']]
                      ],
                      RequestTransition::REOPEN => [
                          'from' => [RequestStatus::COMPLETED, RequestStatus::REJECTED, RequestStatus::CANCELED],
                          'to' => RequestStatus::SENT,
                          'properties' => ['b_name' => 's_reopen', 'css_class' => 'btn-primary', 'roles' => ['author', 'executor','superadmin']]
                      ],
                  ],
                  'callbacks' => [
                      'lock' => [
                          [
                              'do'   => ['object','getLock'],
                          ],
                      ],
                      'unlock' => [
                          [
                              'do'   => ['object','releaseLock'],
                          ],
                      ],
                      'before' => [
                          [
                              'on' => RequestTransition::BACK_TO_AUTHOR,
                              'do'   => ['object','BackToAuthor'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::CANCEL,
                              'do'   => ['object','Cancel'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::TO_ANALYZE,
                              'do'   => ['object','ToAnalyze'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::ANALYZE,
                              'do'   => ['object','ToApprove'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::RETURN,
                              'do'   => ['object','BackToAnalyze'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::REJECT,
                              'from' => RequestStatus::ANALYZE,
                              'do'   => ['object','ToRejectByAdmin'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::REJECT,
                              'from' => RequestStatus::APPROVE,
                              'do'   => ['object','ToReject'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::APPROVE,
                              'do'   => ['object','ToComplete'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::SEND,
                              'do'   => ['object','ToSendPartner'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::RESEND,
                              'do'   => ['object','ToSendPartner'],
                              'args' => [$this->params]
                          ],
                          [
                              'on' => RequestTransition::REOPEN,
                              'do'   => ['object','Reopen'],
                              'args' => [$this->params]
                          ],
                      ],
                      'action' => [
                          [
                              'action' => RequestAction::COMMENT,
                              'do'   => ['object','AddComment'],
                              'args' => [$this->params['ta_comment'] ?? null]
                          ],
                          [
                              'action' => RequestAction::CLONE,
                              'do'   => ['object','CloneRequest'],
                          ],
                          [
                              'action' => RequestAction::ESCALATE,
                              'on' => [RequestStatus::ANALYZE, RequestStatus::APPROVE, RequestStatus::APPROVED, RequestStatus::DELIVERED],
                              'do'   => ['object','Escalate'],
                              'args' => [$this->params['s_escalation_owner'] ?? null]
                          ],
                          [
                              'action' => RequestAction::DELETE,
                              'on' => [RequestStatus::NEW, RequestStatus::ANALYZE, RequestStatus::APPROVE, RequestStatus::APPROVED],
                              'do'   => ['object','DeleteRequest'],
                          ],
                          [
                              'action' => RequestAction::SUSPEND,
                              'do'   => ['object','Suspend'],
                              'args' => [$this->params['ta_comment'] ?? null]
                          ],
                          [
                              'action' => RequestAction::UNSUSPEND,
                              'do'   => ['object','Unsuspend'],
                              'args' => [$this->params['ta_comment'] ?? null]
                          ],
                      ],
                      'guard' => [
                          [
                              'on' => RequestTransition::RESEND,
                              'do' =>  ['object','hasResourcesError'],
                          ]
                      ]
                  ]
              ];
```

So, in the previous example, the graph has 6 possible states, and those can be achieved by applying some transitions to the object. For example, when creating a new `DomainObject`, you would apply the 'create' transition to the object, and after that the state of it would become *pending*.

### Using the state machine

#### Definitions

The state machine is the object actually manipulating your object. By using the state machine you can test if a transition can be applied, actually apply a transition, retrieve the current state, etc. *A state machine is specific to a couple object + graph.* It means that if you want to manipulate another object, or the same object with another graph, *you need another state machine*.

The factory helps you to get the state machine for these couples object + graph. You give an object and a graph name to it, and it will return you the state machine for this couple. If you want to have this factory as a service in your Symfony2 application, please see the [corresponding StateMachineBundle](https://github.com/winzou/StateMachineBundle).

#### Usage

Please refer to the several examples in the `examples` folder.

#### Callbacks

Callbacks are used to guard transitions or execute some code before or after applying transitions.

Guarding callbacks must return a `bool`. If a guard returns `false`, a transition cannot be performed.


##### Credits

This library has been highly inspired by [https://github.com/yohang/Finite](https://github.com/yohang/Finite), but has taken another direction.

##### James' version of SM
Cloned from [https://github.com/sebdesign/state-machine](sebdesign/state-machine)

I've added some new futures
1. Properties - user defined info, that you can use via
 
-  getTransitionProperties
-  getStateProperties
-  hasTransitionProperties
-  hasStateProperties
  
2. lock/unlock - You may implement locking your object, while someone do transition on document that you try to Transit too
3. Action - State can has some actions that don't doing transition and locking document too.
4. Conditions - This is a condition for state action, like Guard for transitions