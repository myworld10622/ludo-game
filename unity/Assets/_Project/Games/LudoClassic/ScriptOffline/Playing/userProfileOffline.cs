
using System;
namespace LudoClassicOffline
{


    public class userProfile
    {
        public int id;

        public string mobileNumber;

        public string displayName;

        public string avatar;

        public string tier;

        public bool pro;

        public double TokenBalance;

        public double TotalBalance;

        public double WithdrawableBalance;

        public double DepositBalance;

        public double BonusBalance;

        public int appVersion;

        public string asyncMode = "";

        public bool prime;

        public userProfile()
        {
        }

        public userProfile(int id, string mobileNum, string displayName, string avatar, string tier, bool pro, int appVersion)
        {
            this.id = id;
            this.mobileNumber = mobileNum;
            this.displayName = displayName;
            this.avatar = avatar;
            this.tier = tier;
            this.pro = pro;
            this.appVersion = appVersion;
        }

        public userProfile(int id, string mobileNum, string displayName, string avatar, string tier, bool pro, int appVersion, string asyncMode, bool prime)
        {
            this.id = id;
            this.mobileNumber = mobileNum;
            this.displayName = displayName;
            this.avatar = avatar;
            this.tier = tier;
            this.pro = pro;
            this.appVersion = appVersion;
            this.asyncMode = (String.IsNullOrEmpty(asyncMode) ? "NA" : asyncMode);
            this.prime = prime;
        }

        public override string ToString()
        {
            return String.Format("id: {0}, mobileNumber: {1}, displayName: {2}, avatar: {3}, tier: {4}, pro: {5}, TokenBalance: {6}, TotalBalance: {7}, WithdrawableBalance: {8}, DepositBalance: {9}, BonusBalance: {10},AppVersion:{11},prime:{12}", new Object[] { this.id, this.mobileNumber, this.displayName, this.avatar, this.tier, this.pro, this.TokenBalance, this.TotalBalance, this.WithdrawableBalance, this.DepositBalance, this.BonusBalance, this.appVersion, this.prime });
        }
    }
}