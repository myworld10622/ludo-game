using System;
using System.Collections.Generic;
using System.IO;
using Best.HTTP;
using Newtonsoft.Json;
using UnityEngine;

namespace LudoClassicOffline
{
    public class LudoFriendApiService : MonoBehaviour
    {
        public static LudoFriendApiService Instance { get; private set; }

        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
            }
            else if (Instance != this)
            {
                Destroy(this);
            }
        }

        private void OnDestroy()
        {
            if (Instance == this)
            {
                Instance = null;
            }
        }

        public void SearchUserByPlayerId(string playerId, Action<LudoFriendApiResult<LudoFriendUserData>> callback)
        {
            if (string.IsNullOrWhiteSpace(playerId))
            {
                callback?.Invoke(LudoFriendApiResult<LudoFriendUserData>.Fail("Player ID is required."));
                return;
            }

            SendRequest<LudoFriendUserEnvelope>(
                HTTPMethods.Get,
                Configuration.GetLudoSearchUserByPlayerIdUrl(playerId.Trim()),
                null,
                response =>
                {
                    if (response != null && response.success && response.data != null)
                    {
                        callback?.Invoke(LudoFriendApiResult<LudoFriendUserData>.Ok(response.data, response.message));
                        return;
                    }

                    callback?.Invoke(LudoFriendApiResult<LudoFriendUserData>.Fail(response?.message ?? "User not found."));
                }
            );
        }

        public void SendFriendRequestByPlayerId(
            string playerId,
            string source,
            string sourceRoomUuid,
            Action<LudoFriendApiResult<LudoFriendRequestData>> callback
        )
        {
            var payload = new Dictionary<string, object>
            {
                { "player_id", playerId?.Trim() ?? string.Empty },
                { "source", string.IsNullOrWhiteSpace(source) ? "lobby_search" : source },
            };

            if (!string.IsNullOrWhiteSpace(sourceRoomUuid))
            {
                payload["source_room_uuid"] = sourceRoomUuid;
            }

            SendRequest<LudoFriendRequestEnvelope>(
                HTTPMethods.Post,
                Configuration.LudoFriendRequestByPlayerIdUrl,
                payload,
                response =>
                {
                    if (response != null && response.success && response.data != null)
                    {
                        callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Ok(response.data, response.message));
                        return;
                    }

                    callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Fail(response?.message ?? "Unable to send friend request."));
                }
            );
        }

        public void SendFriendRequestToUser(
            string receiverUserId,
            string source,
            string sourceRoomUuid,
            Action<LudoFriendApiResult<LudoFriendRequestData>> callback
        )
        {
            if (!int.TryParse(receiverUserId, out int parsedUserId) || parsedUserId <= 0)
            {
                callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Fail("Invalid user ID."));
                return;
            }

            var payload = new Dictionary<string, object>
            {
                { "receiver_user_id", parsedUserId },
                { "source", string.IsNullOrWhiteSpace(source) ? "room" : source },
            };

            if (!string.IsNullOrWhiteSpace(sourceRoomUuid))
            {
                payload["source_room_uuid"] = sourceRoomUuid;
            }

            SendRequest<LudoFriendRequestEnvelope>(
                HTTPMethods.Post,
                Configuration.LudoFriendRequestUrl,
                payload,
                response =>
                {
                    if (response != null && response.success && response.data != null)
                    {
                        callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Ok(response.data, response.message));
                        return;
                    }

                    callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Fail(response?.message ?? "Unable to send friend request."));
                }
            );
        }

        public void ListFriendRequests(Action<LudoFriendApiResult<List<LudoFriendRequestData>>> callback)
        {
            SendRequest<LudoFriendRequestListEnvelope>(
                HTTPMethods.Get,
                Configuration.LudoFriendRequestsUrl,
                null,
                response =>
                {
                    if (response != null && response.success && response.data != null)
                    {
                        callback?.Invoke(LudoFriendApiResult<List<LudoFriendRequestData>>.Ok(response.data, response.message));
                        return;
                    }

                    callback?.Invoke(LudoFriendApiResult<List<LudoFriendRequestData>>.Fail(response?.message ?? "Unable to fetch friend requests."));
                }
            );
        }

        public void ListFriends(Action<LudoFriendApiResult<List<LudoFriendListItemData>>> callback)
        {
            SendRequest<LudoFriendListEnvelope>(
                HTTPMethods.Get,
                Configuration.LudoFriendListUrl,
                null,
                response =>
                {
                    if (response != null && response.success && response.data != null)
                    {
                        callback?.Invoke(LudoFriendApiResult<List<LudoFriendListItemData>>.Ok(response.data, response.message));
                        return;
                    }

                    callback?.Invoke(LudoFriendApiResult<List<LudoFriendListItemData>>.Fail(response?.message ?? "Unable to fetch friends list."));
                }
            );
        }

        public void RespondToFriendRequest(
            string requestUuid,
            string action,
            Action<LudoFriendApiResult<LudoFriendRequestData>> callback
        )
        {
            if (string.IsNullOrWhiteSpace(requestUuid))
            {
                callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Fail("Invalid friend request."));
                return;
            }

            SendRequest<LudoFriendRequestEnvelope>(
                HTTPMethods.Post,
                Configuration.GetLudoFriendRequestActionUrl(requestUuid, action),
                new Dictionary<string, object> { { "action", action } },
                response =>
                {
                    if (response != null && response.success && response.data != null)
                    {
                        callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Ok(response.data, response.message));
                        return;
                    }

                    callback?.Invoke(LudoFriendApiResult<LudoFriendRequestData>.Fail(response?.message ?? "Unable to update friend request."));
                }
            );
        }

        private async void SendRequest<TResponse>(
            HTTPMethods methodType,
            string url,
            Dictionary<string, object> payload,
            Action<TResponse> callback
        ) where TResponse : class
        {
            if (string.IsNullOrWhiteSpace(Configuration.GetToken()))
            {
                callback?.Invoke(null);
                return;
            }

            var request = new HTTPRequest(new Uri(url), methodType);
            request.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            request.SetHeader("Accept", "application/json");

            if (payload != null)
            {
                request.SetHeader("Content-Type", "application/json");
                request.UploadSettings.UploadStream = new MemoryStream(
                    System.Text.Encoding.UTF8.GetBytes(JsonConvert.SerializeObject(payload))
                );
            }

            try
            {
                var response = await request.GetHTTPResponseAsync();
                if (response == null)
                {
                    callback?.Invoke(null);
                    return;
                }

                callback?.Invoke(DeserializeOrFallback<TResponse>(response.DataAsText));
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Friend API request failed: " + ex.Message);
                callback?.Invoke(null);
            }
        }

        private static TResponse DeserializeOrFallback<TResponse>(string json)
            where TResponse : class
        {
            if (string.IsNullOrWhiteSpace(json))
            {
                return null;
            }

            try
            {
                return JsonConvert.DeserializeObject<TResponse>(json);
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Friend API response parse failed: " + ex.Message);
                return null;
            }
        }
    }

    [Serializable]
    public class LudoFriendApiResult<T>
    {
        public bool success;
        public string message;
        public T data;

        public static LudoFriendApiResult<T> Ok(T value, string resultMessage)
        {
            return new LudoFriendApiResult<T>
            {
                success = true,
                message = resultMessage,
                data = value,
            };
        }

        public static LudoFriendApiResult<T> Fail(string resultMessage)
        {
            return new LudoFriendApiResult<T>
            {
                success = false,
                message = resultMessage,
                data = default,
            };
        }
    }

    [Serializable]
    public class LudoFriendUserEnvelope
    {
        public bool success;
        public string message;
        public LudoFriendUserData data;
    }

    [Serializable]
    public class LudoFriendRequestEnvelope
    {
        public bool success;
        public string message;
        public LudoFriendRequestData data;
    }

    [Serializable]
    public class LudoFriendRequestListEnvelope
    {
        public bool success;
        public string message;
        public List<LudoFriendRequestData> data;
    }

    [Serializable]
    public class LudoFriendListEnvelope
    {
        public bool success;
        public string message;
        public List<LudoFriendListItemData> data;
    }

    [Serializable]
    public class LudoFriendRequestData
    {
        public string request_uuid;
        public string status;
        public string source;
        public string source_room_uuid;
        public string message;
        public LudoFriendUserData sender;
        public LudoFriendUserData receiver;
        public string created_at;
        public string updated_at;
    }

    [Serializable]
    public class LudoFriendListItemData
    {
        public string status;
        public string friendship_created_at;
        public LudoFriendUserData friend;
    }

    [Serializable]
    public class LudoFriendUserData
    {
        public int id;
        public string user_code;
        public string uuid;
        public string username;
        public string email;
        public string mobile;
        public string last_login_at;
        public bool is_active;
        public bool is_banned;
        public LudoFriendProfileData profile;
    }

    [Serializable]
    public class LudoFriendProfileData
    {
        public string avatar_url;
        public string first_name;
        public string last_name;
    }
}
