using System;
using System.Collections;
using System.Collections.Specialized;
using Agora.Rtc.LitJson;

namespace Agora.Rtc
{
    public class Optional<T>
    {
        private T value;
        private bool hasValue;

        public Optional()
        {
            hasValue = false;
        }

        public bool HasValue()
        {
            return hasValue;
        }

        public T GetValue()
        {
            return this.value;
        }

        public void SetValue(T val)
        {
            this.hasValue = true;
            this.value = val;
        }

        public void SetEmpty()
        {
            this.hasValue = false;
        }

    }


    public class OptionalJsonParse 
    {
        public virtual void ToJson(JsonWriter writer)
        {
            throw new NotImplementedException();
        }

        public virtual void WriteEnum(LitJson.JsonWriter writer, Object obj)

        {
            Type obj_type = obj.GetType();
            Type e_type = Enum.GetUnderlyingType(obj_type);

            if (e_type == typeof(long))
                writer.Write(Convert.ToInt64(obj));
            else if (e_type == typeof(uint))
                writer.Write(Convert.ToUInt32(obj));
            else if (e_type == typeof(ulong))
                writer.Write(Convert.ToUInt64(obj));
            else if (e_type == typeof(ushort))
                writer.Write(Convert.ToUInt16(obj));
            else if (e_type == typeof(short))
                writer.Write(Convert.ToInt16(obj));
            else if (e_type == typeof(byte))
                writer.Write(Convert.ToByte(obj));
            else if (e_type == typeof(sbyte))
                writer.Write(Convert.ToSByte(obj));
            else
                writer.Write(Convert.ToInt32(obj));
        }

    }

}
