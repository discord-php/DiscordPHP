## Overwrite

### Attributes

- `id`
	- `int`
	- The ID of the Role or Member that the overwrite belongs to.
- `channel_id`
	- `int`
	- The ID of the channel that the overwrite belongs to.
- `type`
	- `string`
	- Either `role` or `member`.

- `allow`
	- [`ChannelPermission`](../permissions/channelpermission.md)
	- The 'allow' permission for the overwrite.
- `deny`
	- [`ChannelPermission`](../permissions/channelpermission.md)
	- The 'deny' permission for the overwrite.

### Functions

There are no functions for the Overwrite part.

### Actions

- Findable: No
- Creatable: No
- Editable: No
- Deletable: Yes