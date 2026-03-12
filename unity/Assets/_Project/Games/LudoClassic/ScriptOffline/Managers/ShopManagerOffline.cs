// using System.Collections;
// using System.Collections.Generic;
// using UnityEngine;
// using UnityEngine.Purchasing;
// using UnityEngine.UI;

// public class ShopManagerOffline : MonoBehaviour,IStoreListener
// {
//     public ConsunableItem consunableItem;
//     IStoreController storeController;


//     [SerializeField] List<Image> player;
//     public Sprite active, inaActive;
//     public GameObject coin, diamond;



//     private void Start()
//     {
//         SetUpBuilder();
//     }

//     public void SetUpBuilder()
//     {
//         var builder = ConfigurationBuilder.Instance(StandardPurchasingModule.Instance());
//         builder.AddProduct(consunableItem.id,ProductType.Consumable);

//         UnityPurchasing.Initialize(this, builder);
//     }
//     public void OnInitialized(IStoreController controller, IExtensionProvider extensions)
//     {
//         storeController = controller;
//     }

//     public void ClickOnBuyButton(int amt)
//     {
//         storeController.InitiatePurchase(consunableItem.id);
//     }

//     public PurchaseProcessingResult ProcessPurchase(PurchaseEventArgs purchaseEvent)
//     {
//         var product = purchaseEvent.purchasedProduct;
//         if (product.definition.id == consunableItem.id)
//         {
//             AddCoin();
//         }

//         return PurchaseProcessingResult.Complete;
//     }

//     public void OnInitializeFailed(InitializationFailureReason error)
//     {
//         throw new System.NotImplementedException();
//     }

//     public void OnInitializeFailed(InitializationFailureReason error, string message)
//     {
//         throw new System.NotImplementedException();
//     }

//     public void OnPurchaseFailed(Product product, PurchaseFailureReason failureReason)
//     {
//         throw new System.NotImplementedException();
//     }


//     public void AddCoin()
//     {
//         Debug.Log("ADD COIN");
//     }

//     public void ClickOnbutton(int no)
//     {

//         foreach (var t in player)
//             t.sprite = inaActive;

//         switch (no)
//         {
//             case 1:
//                 player[0].sprite = active;
//                 coin.SetActive(true);
//                 diamond.SetActive(false);
//               //  SetMSDK("2", "1000000002");
//                 break;
//             case 2:
//                 player[1].sprite = active;
//                 diamond.SetActive(true);
//                 coin.SetActive(false);
//                 // SetMSDK("4", "1000000004");
//                 break;
           
//         }
//     }


// }
// [System.Serializable]
// public class ConsunableItem
// {
//     public string name;
//     public string id;
//     public string dec;
//     public float price;
// }
