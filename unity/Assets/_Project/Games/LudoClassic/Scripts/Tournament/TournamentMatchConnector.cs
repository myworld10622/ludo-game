using System;
using System.Collections.Generic;
using UnityEngine;
using Best.SocketIO;
using Best.SocketIO.Events;
using Newtonsoft.Json;

/// <summary>
/// Handles Socket.IO connection for a single tournament match.
///
/// Flow:
///   1. Call ConnectToMatch(matchData) after tournament room is claimed.
///   2. Listen to events: OnMatchStart, OnDiceResult, OnTokenMoved, OnMatchEnd, etc.
///   3. Call RollDice() / MoveToken() for player actions.
///
/// Events from document.html Section 11:
///   connect_tournament_match, match_room_ready, match_start,
///   roll_dice, dice_result, move_token, token_moved,
///   turn_changed, match_end, player_forfeit, tournament_completed, prize_credited
/// </summary>
public class TournamentMatchConnector : MonoBehaviour
{
    public static TournamentMatchConnector Instance { get; private set; }

    [Header("Socket Config")]
    [SerializeField] private string socketServerUrl = "https://yourdomain.com";
    [SerializeField] private string socketNamespace  = "/ludo_v2";

    // ── Events ────────────────────────────────────────────────────────────────
    public static event Action<MatchRoomReadyData>  OnMatchRoomReady;
    public static event Action<MatchStartData>      OnMatchStart;
    public static event Action<DiceResultData>      OnDiceResult;
    public static event Action<TokenMovedData>      OnTokenMoved;
    public static event Action<TurnChangedData>     OnTurnChanged;
    public static event Action<MatchEndData>        OnMatchEnd;
    public static event Action<PlayerConnectedData> OnPlayerConnected;
    public static event Action<PlayerConnectedData> OnPlayerDisconnected;
    public static event Action<ForfeitData>         OnPlayerForfeit;
    public static event Action<PrizeCreditedData>   OnPrizeCredited;
    public static event Action<BracketUpdateData>   OnBracketUpdate;
    public static event Action<string>              OnNextMatchScheduled;
    public static event Action<string>              OnSocketError;

    private SocketManager _manager;
    private Socket        _socket;
    private bool          _isConnected;

    // Current match state
    public int    CurrentMatchId    { get; private set; }
    public string CurrentRoomId     { get; private set; }
    public int    MyUserId          => int.TryParse(PlayerPrefs.GetString("user_id", "0"), out int id) ? id : 0;
    public bool   IsMyTurn          { get; private set; }
    public int    TurnDeadlineUnix  { get; private set; }

    private void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    // ── Connection ────────────────────────────────────────────────────────────

    public void ConnectToMatch(int matchId, string roomId, int tournamentId)
    {
        CurrentMatchId = matchId;
        CurrentRoomId  = roomId;

        if (_manager == null)
        {
            var options = new SocketOptions { AutoConnect = false };
            _manager = new SocketManager(new Uri(socketServerUrl), options);
        }

        _socket = _manager.GetSocket(socketNamespace);

        RegisterServerEvents();

        // On connect: join the match room
        _socket.On(SocketIOEventTypes.Connect, () =>
        {
            _isConnected = true;
            Debug.Log("[TournamentMatch] Socket connected. Joining match room...");
            JoinMatchRoom(matchId, tournamentId);
        });

        _manager.Open();
    }

    private void JoinMatchRoom(int matchId, int tournamentId)
    {
        var payload = new
        {
            match_id       = matchId,
            user_token     = PlayerPrefs.GetString("auth_token", ""),
            tournament_id  = tournamentId,
        };

        _socket.Emit("connect_tournament_match", JsonConvert.SerializeObject(payload));
    }

    public void Disconnect()
    {
        _manager?.Close();
        _isConnected   = false;
        CurrentMatchId = 0;
        CurrentRoomId  = null;
    }

    // ── Server Event Registration ─────────────────────────────────────────────

    private void RegisterServerEvents()
    {
        // Room ready
        _socket.On<MatchRoomReadyData>("match_room_ready", data =>
            RunOnMainThread(() => OnMatchRoomReady?.Invoke(data)));

        // Players joining/disconnecting
        _socket.On<PlayerConnectedData>("player_connected", data =>
            RunOnMainThread(() => OnPlayerConnected?.Invoke(data)));

        _socket.On<PlayerConnectedData>("player_disconnected", data =>
            RunOnMainThread(() => OnPlayerDisconnected?.Invoke(data)));

        _socket.On<PlayerConnectedData>("player_reconnected", data =>
            RunOnMainThread(() => OnPlayerConnected?.Invoke(data)));

        _socket.On<ForfeitData>("player_forfeit", data =>
            RunOnMainThread(() => OnPlayerForfeit?.Invoke(data)));

        // Game events
        _socket.On<MatchStartData>("match_start", data =>
            RunOnMainThread(() => OnMatchStart?.Invoke(data)));

        _socket.On<DiceResultData>("dice_result", data =>
            RunOnMainThread(() => OnDiceResult?.Invoke(data)));

        _socket.On<TokenMovedData>("token_moved", data =>
            RunOnMainThread(() => OnTokenMoved?.Invoke(data)));

        _socket.On<TurnChangedData>("turn_changed", data =>
            RunOnMainThread(() =>
            {
                IsMyTurn         = data.NextUserId == MyUserId;
                TurnDeadlineUnix = data.TurnDeadline;
                OnTurnChanged?.Invoke(data);
            }));

        _socket.On<MatchEndData>("match_end", data =>
            RunOnMainThread(() => OnMatchEnd?.Invoke(data)));

        // Tournament-level events
        _socket.On<BracketUpdateData>("tournament_bracket_update", data =>
            RunOnMainThread(() => OnBracketUpdate?.Invoke(data)));

        _socket.On<NextMatchScheduledData>("next_match_scheduled", data =>
            RunOnMainThread(() => OnNextMatchScheduled?.Invoke(JsonConvert.SerializeObject(data))));

        _socket.On<PrizeCreditedData>("prize_credited", data =>
            RunOnMainThread(() => OnPrizeCredited?.Invoke(data)));

        _socket.On<SocketErrorPayload>("ludo.error", payload =>
            RunOnMainThread(() => OnSocketError?.Invoke(payload?.Message ?? "Unknown error")));
    }

    // ── Client Actions ────────────────────────────────────────────────────────

    public void RollDice()
    {
        if (!_isConnected || !IsMyTurn) return;

        _socket.Emit("roll_dice", JsonConvert.SerializeObject(new
        {
            user_id  = MyUserId,
            match_id = CurrentMatchId,
        }));
    }

    public void MoveToken(int tokenId, int targetPosition)
    {
        if (!_isConnected || !IsMyTurn) return;

        _socket.Emit("move_token", JsonConvert.SerializeObject(new
        {
            user_id     = MyUserId,
            token_id    = tokenId,
            target_pos  = targetPosition,
        }));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private void RunOnMainThread(Action action)
    {
        // Uses Unity main thread dispatcher pattern
        // UnityMainThreadDispatcher is defined in:
        // Assets/_Project/Core/Scripts/Utilities/UnityMainThreadDispatcher.cs
        UnityMainThreadDispatcher.Instance?.Enqueue(action);
    }

    private void OnDestroy()
    {
        Disconnect();
    }
}

// ── Event Payload Classes ─────────────────────────────────────────────────────

[Serializable]
public class MatchRoomReadyData
{
    [JsonProperty("room_id")]      public string         RoomId;
    [JsonProperty("players")]      public List<MatchPlayer> Players;
    [JsonProperty("match_config")] public MatchConfig    Config;
}

[Serializable]
public class MatchPlayer
{
    [JsonProperty("user_id")]   public int    UserId;
    [JsonProperty("slot")]      public int    Slot;
    [JsonProperty("username")]  public string Username;
    [JsonProperty("is_bot")]    public bool   IsBot;
}

[Serializable]
public class MatchConfig
{
    [JsonProperty("turn_time_limit")] public int TurnTimeLimit;
    [JsonProperty("match_timeout")]   public int MatchTimeout;
}

[Serializable]
public class MatchStartData
{
    [JsonProperty("start_time")]  public string       StartTime;
    [JsonProperty("turn_order")]  public List<int>    TurnOrder;
}

[Serializable]
public class DiceResultData
{
    [JsonProperty("user_id")]     public int       UserId;
    [JsonProperty("value")]       public int       Value;
    [JsonProperty("valid_moves")] public List<int> ValidMoves;
}

[Serializable]
public class TokenMovedData
{
    [JsonProperty("user_id")]  public int    UserId;
    [JsonProperty("token_id")] public int    TokenId;
    [JsonProperty("from")]     public int    From;
    [JsonProperty("to")]       public int    To;
    [JsonProperty("event")]    public string Event; // "capture" | "safe" | "home" | "move"
}

[Serializable]
public class TurnChangedData
{
    [JsonProperty("next_user_id")]    public int  NextUserId;
    [JsonProperty("turn_deadline")]   public int  TurnDeadline; // Unix timestamp
}

[Serializable]
public class MatchEndData
{
    [JsonProperty("winner_id")]   public int                      WinnerId;
    [JsonProperty("positions")]   public List<MatchEndPosition>   Positions;
    [JsonProperty("scores")]      public Dictionary<string, int>  Scores;
}

[Serializable]
public class MatchEndPosition
{
    [JsonProperty("user_id")]  public int UserId;
    [JsonProperty("position")] public int Position;
}

[Serializable]
public class PlayerConnectedData
{
    [JsonProperty("user_id")]            public int    UserId;
    [JsonProperty("slot")]               public int    Slot;
    [JsonProperty("username")]           public string Username;
    [JsonProperty("reconnect_deadline")] public int    ReconnectDeadline;
}

[Serializable]
public class ForfeitData
{
    [JsonProperty("user_id")] public int    UserId;
    [JsonProperty("reason")]  public string Reason;
}

[Serializable]
public class BracketUpdateData
{
    [JsonProperty("tournament_id")] public int    TournamentId;
    [JsonProperty("bracket")]       public object Bracket;
}

[Serializable]
public class PrizeCreditedData
{
    [JsonProperty("amount")]          public float  Amount;
    [JsonProperty("position")]        public int    Position;
    [JsonProperty("tournament_name")] public string TournamentName;
}

[Serializable]
public class NextMatchScheduledData
{
    [JsonProperty("scheduled_at")] public string ScheduledAt;
    [JsonProperty("opponent")]     public string Opponent;
    [JsonProperty("match_id")]     public int    MatchId;
}

[Serializable]
public class SocketErrorPayload
{
    [JsonProperty("message")] public string Message;
}

// UnityMainThreadDispatcher is defined in:
// Assets/_Project/Core/Scripts/Utilities/UnityMainThreadDispatcher.cs
