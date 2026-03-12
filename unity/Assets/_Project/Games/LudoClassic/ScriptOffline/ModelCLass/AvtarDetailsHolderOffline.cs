using System.Collections.Generic;

namespace LudoClassicOffline
{
    [System.Serializable]
    public class ProfileAvtarData
    {
        public bool isActive;
    }
    [System.Serializable]
    public class ProfileAvtar
    {
        public List<ProfileAvtarData> players;
    }
}