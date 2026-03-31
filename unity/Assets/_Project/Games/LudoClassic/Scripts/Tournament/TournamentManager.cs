using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.Networking;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;

namespace LudoClassic.Tournament
{
    /// <summary>
    /// Central manager for all tournament API calls.
    /// Attach to a persistent GameObject (DontDestroyOnLoad).
    /// </summary>
    public class TournamentManager : MonoBehaviour
    {
        public static TournamentManager Instance { get; private set; }

        [Header("API Config")]
        [SerializeField] private string apiBaseUrl = "https://yourdomain.com/api/v1";

        // Events
        public static event Action<List<TournamentData>> OnTournamentsLoaded;
        public static event Action<TournamentData>       OnTournamentDetailLoaded;
        public static event Action<string>               OnRegistrationSuccess;
        public static event Action<string>               OnRegistrationFailed;
        public static event Action<string>               OnApiError;

        private string AuthToken => PlayerPrefs.GetString("auth_token", "");

        private void Awake()
        {
            if (Instance != null && Instance != this)
            {
                Destroy(gameObject);
                return;
            }
            Instance = this;
            DontDestroyOnLoad(gameObject);
        }

        // ── Public API Methods ────────────────────────────────────────────────

        public void FetchPublicTournaments(string statusFilter = "registration_open")
        {
            StartCoroutine(GetTournaments(statusFilter));
        }

        public void FetchTournamentById(int tournamentId)
        {
            StartCoroutine(GetTournamentDetail(tournamentId));
        }

        public void FetchPrivateTournament(string inviteCode, string password = null)
        {
            StartCoroutine(GetPrivateTournament(inviteCode, password));
        }

        public void RegisterForTournament(int tournamentId, Action<bool, string> callback)
        {
            StartCoroutine(PostRegister(tournamentId, callback));
        }

        public void CancelRegistration(int tournamentId, Action<bool, string> callback)
        {
            StartCoroutine(DeleteRegistration(tournamentId, callback));
        }

        public void FetchBracket(int tournamentId, Action<BracketData> callback)
        {
            StartCoroutine(GetBracket(tournamentId, callback));
        }

        public void FetchMyHistory(Action<List<MyTournamentEntry>> callback)
        {
            StartCoroutine(GetMyHistory(callback));
        }

        // ── Coroutines ────────────────────────────────────────────────────────

        private IEnumerator GetTournaments(string statusFilter)
        {
            string url = $"{apiBaseUrl}/tournaments?status={statusFilter}";
            using var req = UnityWebRequest.Get(url);
            SetHeaders(req);
            yield return req.SendWebRequest();

            if (req.result != UnityWebRequest.Result.Success)
            {
                OnApiError?.Invoke(req.error);
                yield break;
            }

            try
            {
                var json = JObject.Parse(req.downloadHandler.text);
                var list = json["data"]?["data"]?.ToObject<List<TournamentData>>();
                OnTournamentsLoaded?.Invoke(list ?? new List<TournamentData>());
            }
            catch (Exception e)
            {
                OnApiError?.Invoke($"Parse error: {e.Message}");
            }
        }

        private IEnumerator GetTournamentDetail(int tournamentId)
        {
            string url = $"{apiBaseUrl}/tournaments/{tournamentId}";
            using var req = UnityWebRequest.Get(url);
            SetHeaders(req);
            yield return req.SendWebRequest();

            if (req.result != UnityWebRequest.Result.Success)
            {
                OnApiError?.Invoke(req.error);
                yield break;
            }

            try
            {
                var json = JObject.Parse(req.downloadHandler.text);
                var data = json["data"]?.ToObject<TournamentData>();
                if (data != null) OnTournamentDetailLoaded?.Invoke(data);
            }
            catch (Exception e)
            {
                OnApiError?.Invoke($"Parse error: {e.Message}");
            }
        }

        private IEnumerator GetPrivateTournament(string inviteCode, string password)
        {
            string url = $"{apiBaseUrl}/tournaments/private/{inviteCode}";

            if (!string.IsNullOrEmpty(password))
            {
                string bodyJson = JsonConvert.SerializeObject(new { password });
                using var req = new UnityWebRequest(url, "POST");
                req.uploadHandler   = new UploadHandlerRaw(System.Text.Encoding.UTF8.GetBytes(bodyJson));
                req.downloadHandler = new DownloadHandlerBuffer();
                SetHeaders(req);
                yield return req.SendWebRequest();

                HandleDetailResponse(req);
            }
            else
            {
                using var req = UnityWebRequest.Get(url);
                SetHeaders(req);
                yield return req.SendWebRequest();
                HandleDetailResponse(req);
            }
        }

        private void HandleDetailResponse(UnityWebRequest req)
        {
            if (req.result != UnityWebRequest.Result.Success)
            {
                OnApiError?.Invoke(req.error);
                return;
            }
            try
            {
                var json = JObject.Parse(req.downloadHandler.text);
                var data = json["data"]?.ToObject<TournamentData>();
                if (data != null) OnTournamentDetailLoaded?.Invoke(data);
            }
            catch (Exception e)
            {
                OnApiError?.Invoke($"Parse error: {e.Message}");
            }
        }

        private IEnumerator PostRegister(int tournamentId, Action<bool, string> callback)
        {
            string url      = $"{apiBaseUrl}/tournaments/{tournamentId}/register";
            string bodyJson = "{}";

            using var req = new UnityWebRequest(url, "POST");
            req.uploadHandler   = new UploadHandlerRaw(System.Text.Encoding.UTF8.GetBytes(bodyJson));
            req.downloadHandler = new DownloadHandlerBuffer();
            SetHeaders(req);
            yield return req.SendWebRequest();

            var json    = JObject.Parse(req.downloadHandler.text ?? "{}");
            string msg  = json["message"]?.ToString() ?? req.error;
            bool success = req.result == UnityWebRequest.Result.Success;

            callback?.Invoke(success, msg);

            if (success)
                OnRegistrationSuccess?.Invoke(msg);
            else
                OnRegistrationFailed?.Invoke(msg);
        }

        private IEnumerator DeleteRegistration(int tournamentId, Action<bool, string> callback)
        {
            string url = $"{apiBaseUrl}/tournaments/{tournamentId}/register";
            using var req = UnityWebRequest.Delete(url);
            req.downloadHandler = new DownloadHandlerBuffer();
            SetHeaders(req);
            yield return req.SendWebRequest();

            var json    = JObject.Parse(req.downloadHandler.text ?? "{}");
            string msg  = json["message"]?.ToString() ?? req.error;
            bool success = req.result == UnityWebRequest.Result.Success;
            callback?.Invoke(success, msg);
        }

        private IEnumerator GetBracket(int tournamentId, Action<BracketData> callback)
        {
            string url = $"{apiBaseUrl}/tournaments/{tournamentId}/bracket";
            using var req = UnityWebRequest.Get(url);
            SetHeaders(req);
            yield return req.SendWebRequest();

            if (req.result != UnityWebRequest.Result.Success)
            {
                OnApiError?.Invoke(req.error);
                callback?.Invoke(null);
                yield break;
            }

            try
            {
                var json = JObject.Parse(req.downloadHandler.text);
                var data = json["data"]?.ToObject<BracketData>();
                callback?.Invoke(data);
            }
            catch (Exception e)
            {
                OnApiError?.Invoke($"Parse error: {e.Message}");
                callback?.Invoke(null);
            }
        }

        private IEnumerator GetMyHistory(Action<List<MyTournamentEntry>> callback)
        {
            string url = $"{apiBaseUrl}/tournaments/me/history";
            using var req = UnityWebRequest.Get(url);
            SetHeaders(req);
            yield return req.SendWebRequest();

            if (req.result != UnityWebRequest.Result.Success)
            {
                callback?.Invoke(new List<MyTournamentEntry>());
                yield break;
            }

            try
            {
                var json = JObject.Parse(req.downloadHandler.text);
                var list = json["data"]?["data"]?.ToObject<List<MyTournamentEntry>>();
                callback?.Invoke(list ?? new List<MyTournamentEntry>());
            }
            catch
            {
                callback?.Invoke(new List<MyTournamentEntry>());
            }
        }

        // ── Helpers ───────────────────────────────────────────────────────────

        private void SetHeaders(UnityWebRequest req)
        {
            req.SetRequestHeader("Accept", "application/json");
            req.SetRequestHeader("Content-Type", "application/json");
            if (!string.IsNullOrEmpty(AuthToken))
                req.SetRequestHeader("Authorization", $"Bearer {AuthToken}");
        }
    }

    // ── Data Models ───────────────────────────────────────────────────────────

    [Serializable]
    public class TournamentData
    {
        [JsonProperty("id")]           public int    Id;
        [JsonProperty("name")]         public string Name;
        [JsonProperty("type")]         public string Type;       // public / private
        [JsonProperty("format")]       public string Format;     // knockout / round_robin / etc.
        [JsonProperty("status")]       public string Status;
        [JsonProperty("entry_fee")]    public float  EntryFee;
        [JsonProperty("max_players")]  public int    MaxPlayers;
        [JsonProperty("current_players")] public int CurrentPlayers;
        [JsonProperty("total_prize_pool")] public float TotalPrizePool;
        [JsonProperty("platform_fee_pct")] public float PlatformFeePct;
        [JsonProperty("players_per_match")] public int PlayersPerMatch;
        [JsonProperty("bot_allowed")]  public bool   BotAllowed;
        [JsonProperty("invite_code")]  public string InviteCode;
        [JsonProperty("registration_start_at")] public string RegistrationStartAt;
        [JsonProperty("registration_end_at")]   public string RegistrationEndAt;
        [JsonProperty("tournament_start_at")]   public string TournamentStartAt;
        [JsonProperty("prizes")]       public List<PrizeData> Prizes;

        public bool IsFull => CurrentPlayers >= MaxPlayers;
        public bool CanJoin => Status == "registration_open" && !IsFull;
    }

    [Serializable]
    public class PrizeData
    {
        [JsonProperty("position")]     public int   Position;
        [JsonProperty("prize_pct")]    public float PrizePct;
        [JsonProperty("prize_amount")] public float PrizeAmount;
    }

    [Serializable]
    public class BracketData
    {
        [JsonProperty("tournament_id")] public int TournamentId;
        [JsonProperty("format")]        public string Format;
        [JsonProperty("rounds")]        public Dictionary<string, List<BracketMatch>> Rounds;
    }

    [Serializable]
    public class BracketMatch
    {
        [JsonProperty("id")]           public int    Id;
        [JsonProperty("match_number")] public int    MatchNumber;
        [JsonProperty("status")]       public string Status;
        [JsonProperty("scheduled_at")] public string ScheduledAt;
        [JsonProperty("players")]      public List<BracketPlayer> Players;
        [JsonProperty("winner")]       public string Winner;
    }

    [Serializable]
    public class BracketPlayer
    {
        [JsonProperty("slot")]         public int    Slot;
        [JsonProperty("name")]         public string Name;
        [JsonProperty("is_bot")]       public bool   IsBot;
        [JsonProperty("score")]        public int    Score;
        [JsonProperty("finish_pos")]   public int?   FinishPos;
        [JsonProperty("result")]       public string Result;
    }

    [Serializable]
    public class MyTournamentEntry
    {
        [JsonProperty("id")]             public int    Id;
        [JsonProperty("status")]         public string Status;
        [JsonProperty("final_position")] public int?   FinalPosition;
        [JsonProperty("prize_won")]      public float  PrizeWon;
        [JsonProperty("entry_fee_paid")] public float  EntryFeePaid;
        [JsonProperty("tournament")]     public TournamentData Tournament;
    }
}
