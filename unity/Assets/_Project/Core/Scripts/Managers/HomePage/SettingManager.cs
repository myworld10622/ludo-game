using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using EasyButtons;
using UnityEngine;
using UnityEngine.UI;

public class SettingManager : MonoBehaviour
{
    public Toggle soundToggle;
    public Toggle musicToggle;

    public Button PrivacyButton;
    public Button TurmsConditionButton;

    public List<Button> AllHomeScreenButtons = new List<Button>();

    private bool _isInitializing;

    void OnEnable()
    {
        /*   if (!PlayerPrefs.HasKey("FirstTimeSetup"))
          {
              // First-time setup: Set default values
              PlayerPrefs.SetString("music", "on");
              PlayerPrefs.SetString("sound", "on");
              PlayerPrefs.SetString("FirstTimeSetup", "true");
              PlayerPrefs.Save();
          } */
        // Initialize toggles based on configuration settings
        if (!PlayerPrefs.HasKey("music"))
            PlayerPrefs.SetString("music", "on");
        if (!PlayerPrefs.HasKey("sound"))
            PlayerPrefs.SetString("sound", "on");

        if (musicToggle == null || soundToggle == null)
        {
            Debug.LogWarning("SettingManager toggles are not assigned.");
            return;
        }

        _isInitializing = true;

        musicToggle.onValueChanged.RemoveListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.RemoveListener(OnSoundToggleChanged);

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        Debug.Log($"Music Prefs == {Configuration.GetMusic()}   || Sound Prefs == {Configuration.GetSound()}");

        OnMusicToggleChanged(musicToggle.isOn);

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);
        _isInitializing = false;

        AllHomeScreenButtons.RemoveAll(button => button == null);
        foreach (Button button in AllHomeScreenButtons)
        {
            button.onClick.AddListener(PlayButtonSound);
        }
        if (PrivacyButton != null)
        {
            PrivacyButton.onClick.RemoveListener(OnClickPrivacyAndPolicy);
            PrivacyButton.onClick.AddListener(OnClickPrivacyAndPolicy);
        }

        if (TurmsConditionButton != null)
        {
            TurmsConditionButton.onClick.RemoveListener(OpenTermsCondition);
            TurmsConditionButton.onClick.AddListener(OpenTermsCondition);
        }
        //ResetSettingButtons();
    }

    private void OnDisable()
    {
        if (musicToggle != null)
        {
            musicToggle.onValueChanged.RemoveListener(OnMusicToggleChanged);
        }

        if (soundToggle != null)
        {
            soundToggle.onValueChanged.RemoveListener(OnSoundToggleChanged);
        }

        foreach (Button button in AllHomeScreenButtons)
        {
            if (button != null)
            {
                button.onClick.RemoveListener(PlayButtonSound);
            }
        }

        if (PrivacyButton != null)
        {
            PrivacyButton.onClick.RemoveListener(OnClickPrivacyAndPolicy);
        }

        if (TurmsConditionButton != null)
        {
            TurmsConditionButton.onClick.RemoveListener(OpenTermsCondition);
        }
    }
    public void ResetSettingButtons()
    {
        //PlayerPrefs.DeleteAll();
        //if (!PlayerPrefs.HasKey("music"))
        //{
        //    PlayerPrefs.SetString("music", "on");
        //    PlayerPrefs.Save();
        //}

        //if (!PlayerPrefs.HasKey("sound"))
        //{
        //    PlayerPrefs.SetString("sound", "on");
        //    PlayerPrefs.Save();
        //}

        //PlayerPrefs.SetString("music", "on");
        //PlayerPrefs.SetString("sound", "on");
        //musicToggle.isOn = Configuration.GetMusic() == "on";
        //soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        //musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        //soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);

    }

    private void PlayButtonSound()
    {
        if (AudioManager._instance == null)
        {
            return;
        }

        AudioManager._instance.ButtonClick();
    }

    private void OnMusicToggleChanged(bool isOn)
    {
        Debug.Log($"Music: {isOn}");

        PlayerPrefs.SetString("music", isOn ? "on" : "");
        PlayerPrefs.Save();

        if (_isInitializing || AudioManager._instance == null)
        {
            return;
        }

        if (isOn)
        {
            AudioManager._instance.PlayBackgroundAudio();
        }
        else
        {
            AudioManager._instance.StopBackgroundAudio();
        }
    }

    private void OnSoundToggleChanged(bool isOn)
    {
        Debug.Log($"Sound: {isOn}");

        PlayerPrefs.SetString("sound", isOn ? "on" : "");
        PlayerPrefs.Save();
        if (_isInitializing || AudioManager._instance == null)
        {
            return;
        }

        if (!isOn)
            AudioManager._instance.StopEffect();
    }
    public void OnClickPrivacyAndPolicy()
    {
        openwebview(Configuration.Website + "privacy-policy");
    }

    public void OpenTermsCondition()
    {
        openwebview(Configuration.Website + "terms-conditions");
    }

    public void openwebview(string url)
    {
        Application.OpenURL(url);
    }

    [Button]
    public void AssignALLButtons()
    {
        AllHomeScreenButtons.RemoveAll(button => button == null);

        /*      AllHomeScreenButtons.Clear();
             var buttons = Resources.FindObjectsOfTypeAll<Button>();
             AllHomeScreenButtons = buttons.ToList(); */
    }
}
