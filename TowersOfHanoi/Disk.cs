using TowersOfHanoi.Interfaces;

namespace TowersOfHanoi;
public class Disk : IComparable<Disk>, IPrintable
{
    public int Size { get; private set; }

    public Disk(int diskSize)
    {
        Size = diskSize;
    }

    public int CompareTo(Disk? other)
    {
        if (other == null) return 1;
        return Size.CompareTo(other.Size);
        throw new NotImplementedException();
    }

    public void Print()
    {
        Console.Write($"[ {Size} ] ");
    }
}
