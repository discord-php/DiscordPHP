---
title: "Interactions"
---

Interactions are utilized in message components and slash commands.

## Attributes

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

## Functions

| name                                                             | description                                                                                      | return type        |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ | ------------------ |
| `acknowledge()`                                                  | acknowledges the interaction                                                                     | `Promise<void>`    |
| `adknowledgeWithResponse()`                                      | adknowledges the interaction, creating a placeholder response to be updated                      | `Promise<void>`    |
| `updateMessage(MessageBuilder $message)`                         | updates the message the interaction was triggered from. only for message component interactions. | `Promise<void>`    |
| `getOriginalResponse()`                                          | gets the original interaction response.                                                          | `Promise<Message>` |
| `updateOriginalResponse(MessageBuilder $message)`                | updates the original interaction response.                                                       | `Promise<Message>` |
| `deleteOriginalResponse()`                                       | deletes the original interaction response.                                                       | `Promise<void>`    |
| `sendFollowUpMessage(MessageBuilder $builder, ?bool $ephemeral)` | sends a follow up message to the interaction. ephemeral is defalt false.                         | `Promise<Message>` |
| `respondWithMessage(MessageBuilder $builder, ?bool $ephemeral)`  | responds to the interaction with a message. ephemeral is default false.                          | `Promise<void>`    |