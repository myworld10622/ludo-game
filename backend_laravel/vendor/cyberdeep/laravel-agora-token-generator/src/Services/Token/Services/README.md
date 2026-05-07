# Agora Token Services

## Troubleshooting Token Issues

If you're experiencing "invalid token" errors when connecting to Agora, check the following:

1. **App ID and App Certificate Format**: Ensure your App ID and App Certificate are correct. They should be copied exactly from the Agora Console.

2. **Channel Name Formatting**: Use simple channel names without special characters or concatenation. The channel name used to generate the token must match exactly with the channel name used when connecting.

3. **User ID Consistency**: The user ID in the token must match the user ID used when connecting to Agora.

4. **Token Expiration**: Make sure the token hasn't expired. Default expiration is 3600 seconds (1 hour).

5. **Privilege Settings**: For audio-only channels, make sure the video privilege is properly set.

6. **Token Version**: Ensure your client is using the correct token version (v1 or v2) that matches what you're generating.

For further assistance, check the [Agora Authentication Documentation](https://docs.agora.io/en/video-calling/develop/authentication-workflow).
