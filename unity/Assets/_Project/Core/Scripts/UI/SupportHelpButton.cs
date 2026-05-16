using UnityEngine;

/// <summary>
/// Floating Telegram support button shown alongside deposit/withdrawal panels.
/// Pure OnGUI — no scene editing required. Auto-injects via EnsureExists().
/// Admin can update the URL at runtime via Configuration.SetTelegramSupportUrl().
/// Telegram icon loaded from Resources/TelegramIcon.png
/// </summary>
public class SupportHelpButton : MonoBehaviour
{
    public static SupportHelpButton Instance { get; private set; }

    private static bool _visible;
    private Texture2D _bgTex;
    private Texture2D _telegramIcon;
    private GUIStyle _btnStyle;
    private GUIStyle _labelStyle;
    private bool _stylesBuilt;

    private static readonly Color TelegramBlue = new Color(0.141f, 0.631f, 0.871f, 1f);
    private static readonly Color TelegramDark  = new Color(0.082f, 0.475f, 0.667f, 1f);

    private void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    public static void Show() => _visible = true;
    public static void Hide() => _visible = false;

    public static void EnsureExists()
    {
        if (Instance != null) return;
        new GameObject("SupportHelpButton").AddComponent<SupportHelpButton>();
    }

    private void BuildStyles()
    {
        if (_stylesBuilt) return;
        _stylesBuilt = true;

        _bgTex = MakeCircleTex(64, TelegramBlue);

        // Load proper Telegram icon from Resources
        Texture2D loaded = Resources.Load<Texture2D>("TelegramIcon");
        _telegramIcon = loaded != null ? loaded : MakeFallbackIconTex(64);

        _btnStyle = new GUIStyle
        {
            normal  = { background = _bgTex },
            hover   = { background = MakeCircleTex(64, TelegramDark) },
            active  = { background = MakeCircleTex(64, TelegramDark) },
            border  = new RectOffset(32, 32, 32, 32),
            padding = new RectOffset(0, 0, 0, 0),
        };

        _labelStyle = new GUIStyle
        {
            normal    = { textColor = Color.white },
            alignment = TextAnchor.MiddleCenter,
            fontSize  = Mathf.RoundToInt(Screen.height * 0.018f),
            fontStyle = FontStyle.Bold,
        };
    }

    private void OnGUI()
    {
        if (!_visible) return;
        BuildStyles();

        float size   = Screen.height * 0.09f;
        float margin = Screen.height * 0.02f;

        float x = Screen.width  - size - margin;
        float y = Screen.height - size - margin * 3.5f; // above bottom edge

        Rect btnRect = new Rect(x, y, size, size);

        // Drop shadow
        GUI.color = new Color(0f, 0f, 0f, 0.3f);
        GUI.DrawTexture(new Rect(x + 3, y + 4, size, size), _bgTex);
        GUI.color = Color.white;

        // Circular blue background button
        if (GUI.Button(btnRect, GUIContent.none, _btnStyle))
            Application.OpenURL(Configuration.GetTelegramSupportUrl());

        // Telegram icon centered inside button
        float pad = size * 0.18f;
        GUI.DrawTexture(new Rect(x + pad, y + pad, size - pad * 2f, size - pad * 2f), _telegramIcon);

        // "Help" label below
        GUI.Label(new Rect(x - size * 0.1f, y + size + 2f, size * 1.2f, size * 0.4f), "Help", _labelStyle);
    }

    private static Texture2D MakeCircleTex(int size, Color col)
    {
        var tex = new Texture2D(size, size, TextureFormat.ARGB32, false);
        tex.filterMode = FilterMode.Bilinear;
        float r = size / 2f;
        for (int y = 0; y < size; y++)
        for (int x = 0; x < size; x++)
        {
            float dx = x - r + 0.5f;
            float dy = y - r + 0.5f;
            tex.SetPixel(x, y, dx * dx + dy * dy <= r * r ? col : Color.clear);
        }
        tex.Apply();
        return tex;
    }

    // Fallback: simple white paper-plane shape if icon fails to load
    private static Texture2D MakeFallbackIconTex(int size)
    {
        var tex = new Texture2D(size, size, TextureFormat.ARGB32, false);
        for (int y = 0; y < size; y++)
        for (int x = 0; x < size; x++)
            tex.SetPixel(x, y, Color.clear);

        FillTriangle(tex, size,
            new Vector2(0.08f, 0.5f), new Vector2(0.92f, 0.15f), new Vector2(0.92f, 0.85f), Color.white);
        FillTriangle(tex, size,
            new Vector2(0.45f, 0.5f), new Vector2(0.92f, 0.42f), new Vector2(0.92f, 0.85f),
            new Color(0.141f, 0.631f, 0.871f, 1f));
        tex.Apply();
        return tex;
    }

    private static void FillTriangle(Texture2D tex, int size, Vector2 a, Vector2 b, Vector2 c, Color col)
    {
        Vector2 pa = new Vector2(a.x * size, a.y * size);
        Vector2 pb = new Vector2(b.x * size, b.y * size);
        Vector2 pc = new Vector2(c.x * size, c.y * size);
        int minX = Mathf.FloorToInt(Mathf.Min(pa.x, pb.x, pc.x));
        int maxX = Mathf.CeilToInt(Mathf.Max(pa.x, pb.x, pc.x));
        int minY = Mathf.FloorToInt(Mathf.Min(pa.y, pb.y, pc.y));
        int maxY = Mathf.CeilToInt(Mathf.Max(pa.y, pb.y, pc.y));
        for (int y = minY; y <= maxY; y++)
        for (int x = minX; x <= maxX; x++)
        {
            if (x < 0 || x >= size || y < 0 || y >= size) continue;
            if (InTriangle(new Vector2(x + 0.5f, y + 0.5f), pa, pb, pc))
                tex.SetPixel(x, y, col);
        }
    }

    private static bool InTriangle(Vector2 p, Vector2 a, Vector2 b, Vector2 c)
    {
        float d1 = (p.x - b.x) * (a.y - b.y) - (a.x - b.x) * (p.y - b.y);
        float d2 = (p.x - c.x) * (b.y - c.y) - (b.x - c.x) * (p.y - c.y);
        float d3 = (p.x - a.x) * (c.y - a.y) - (c.x - a.x) * (p.y - a.y);
        bool hasNeg = d1 < 0 || d2 < 0 || d3 < 0;
        bool hasPos = d1 > 0 || d2 > 0 || d3 > 0;
        return !(hasNeg && hasPos);
    }
}
