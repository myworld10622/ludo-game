using System;
using System.Text.RegularExpressions;

namespace LudoClassicOffline
{
    public static class LudoDisplayNameUtility
    {
        private static readonly Regex NumericIdRegex = new Regex(@"^\d{6,12}$", RegexOptions.Compiled);
        private static readonly Regex GenericPlayerLabelRegex = new Regex(@"^player\s+\d+$", RegexOptions.Compiled | RegexOptions.IgnoreCase);

        public static string LocalPlayerLabel()
        {
            string localId = NormalizePublicId(Configuration.GetId());
            return !string.IsNullOrEmpty(localId) ? localId : "Player 1";
        }

        public static string NeutralSeatLabel(int seatIndex)
        {
            return "Player " + Math.Max(1, seatIndex + 1);
        }

        public static string ResolveDisplayName(string userId, string rawName, int seatIndex)
        {
            if (LooksLikeBot(userId, rawName))
            {
                return BuildBotPublicId(userId, rawName, seatIndex);
            }

            string normalizedId = NormalizePublicId(userId);
            if (!string.IsNullOrEmpty(normalizedId))
            {
                return normalizedId;
            }

            string normalizedNameId = NormalizePublicId(rawName);
            if (!string.IsNullOrEmpty(normalizedNameId))
            {
                return normalizedNameId;
            }

            string localName = (Configuration.GetName() ?? string.Empty).Trim();
            if (!string.IsNullOrEmpty(localName) &&
                string.Equals((rawName ?? string.Empty).Trim(), localName, StringComparison.OrdinalIgnoreCase))
            {
                return LocalPlayerLabel();
            }

            return NeutralSeatLabel(seatIndex);
        }

        public static bool LooksLikeBot(string userId, string rawName)
        {
            string id = (userId ?? string.Empty).Trim().ToLowerInvariant();
            string name = (rawName ?? string.Empty).Trim().ToLowerInvariant();
            bool hasRealPublicId = !string.IsNullOrEmpty(NormalizePublicId(userId));

            return id.StartsWith("bot")
                   || id.Contains("bot-")
                   || id.Contains("tournament-bot")
                   || name.StartsWith("bot")
                   || name.StartsWith("com.")
                   || name.StartsWith("computer")
                   || name.Contains(" bot")
                   || name.Contains("ai")
                   || (!hasRealPublicId && GenericPlayerLabelRegex.IsMatch(name))
                   || (id.Length > 0 && id.Length <= 2 && !hasRealPublicId);
        }

        private static string BuildBotPublicId(string userId, string rawName, int seatIndex)
        {
            string seed = $"{userId}|{rawName}|{seatIndex}";
            unchecked
            {
                int hash = 17;
                foreach (char ch in seed)
                {
                    hash = (hash * 31) + ch;
                }

                int positive = Math.Abs(hash);
                int eightDigit = 10000000 + (positive % 90000000);
                return eightDigit.ToString();
            }
        }

        private static string NormalizePublicId(string value)
        {
            string trimmed = (value ?? string.Empty).Trim();
            return NumericIdRegex.IsMatch(trimmed) ? trimmed : string.Empty;
        }
    }
}
