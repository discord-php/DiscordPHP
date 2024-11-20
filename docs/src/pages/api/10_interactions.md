---
title: "Interactions"
---

Interactions are utilized in message components and slash commands.

### Attributes

| name           | type               | description                                          |
| -------------- | ------------------ | ---------------------------------------------------- |
| id             | string             | id of the interaction.                               |
| application_id | string             | id of the application associated to the interaction. |
| int            | type               | type of interaction.                                 |
| data           | `?InteractionData` | data associated with the interaction.                |
| guild          | `?Guild`           | guild interaction was triggered from, null if DM.    |
| channel        | `?Channel`         | channel interaction was triggered from.              |
| member         | `?Member`          | member that triggered interaction.                   |
| user           | `User`             | user that triggered interaction.                     |
| token          | string             | internal token for responding to interaction.        |
| version        | int                | version of interaction.                              |
| message        | `?Message`         | message that triggered interaction.                  |
| locale         | ?string            | The selected language of the invoking user.          |
| guild_locale   | ?string            | The guild's preferred locale, if invoked in a guild. |

The locale list can be seen on [Discord API reference](https://discord.com/developers/docs/reference#locales).

### Functions on interaction create

The following functions are used to respond an interaction after being created `Event::INTERACTION_CREATE`,
responding interaction with wrong type throws a `LogicException`

| name                                                                                       | description                                                                 | valid for interaction type                                 |
| ------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `acknowledgeWithResponse(?bool $ephemeral)`                                                | acknowledges the interaction, creating a placeholder response to be updated | `APPLICATION_COMMAND`, `MESSAGE_COMPONENT`, `MODAL_SUBMIT` |
| `acknowledge()`                                                                            | defer the interaction                                                       | `MESSAGE_COMPONENT`, `MODAL_SUBMIT`                        |
| `respondWithMessage(MessageBuilder $builder, ?bool $ephemeral)`                            | responds to the interaction with a message. ephemeral is default false      | `APPLICATION_COMMAND`, `MESSAGE_COMPONENT`, `MODAL_SUBMIT` |
| `autoCompleteResult(array $choices)`                                                       | responds a suggestion to options with auto complete                         | `APPLICATION_COMMAND_AUTOCOMPLETE`                         |
| `showModal(string $title, string $custom_id, array $components, ?callable $submit = null)` | responds to the interaction with a popup modal                              | other than `PING` and `MODAL_SUBMIT`                       |

### Functions after interaction response

The following functions can be only used after interaction respond above,
otherwise throws a `RuntimeException` "Interaction has not been responded to."

| name                                                                 | description                                                                                    | return             |
| -------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | ------------------ |
| `updateMessage(MessageBuilder $message)`                             | updates the message the interaction was triggered from. only for message component interaction | `Promise<void>`    |
| `getOriginalResponse()`                                              | gets the original interaction response                                                         | `Promise<Message>` |
| `updateOriginalResponse(MessageBuilder $message)`                    | updates the original interaction response                                                      | `Promise<Message>` |
| `deleteOriginalResponse()`                                           | deletes the original interaction response                                                      | `Promise<void>`    |
| `sendFollowUpMessage(MessageBuilder $builder, ?bool $ephemeral)`     | sends a follow up message to the interaction. ephemeral is defalt false                        | `Promise<Message>` |
| `getFollowUpMessage(string $message_id)`                             | gets a non ephemeral follow up message from the interaction                                    | `Promise<Message>` |
| `updateFollowUpMessage(string $message_id, MessageBuilder $builder)` | updates the follow up message of the interaction                                               | `Promise<Message>` |
| `deleteFollowUpMessage(string $message_id)`                          | deletes a follow up message from the interaction                                               | `Promise<void>`    |
