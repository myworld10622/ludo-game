using System.Collections.Generic;
    
namespace LudoClassicOffline
{
    [System.Serializable]
    public class PopUpData
    {
        public bool isPopup ;
        public string popupType ;
        public string title ;
        public string message ;
        public int buttonCounts ;
        public List<string> button_text ;
        public List<string> button_color ;
        public List<string> button_methods ;
    }
    [System.Serializable]
    public class PopUp
    {
        public string en ;
        public PopUpData data ;
    }
}