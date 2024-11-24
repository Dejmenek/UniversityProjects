using TowersOfHanoi.Exceptions;
using TowersOfHanoi.Interfaces;

namespace TowersOfHanoi;
public class Tower<T> where T : IPrintable, IComparable<T>
{
    private Stack<T> _elements = new Stack<T>();

    public void Push(T element)
    {
        if (IsMoveValid(element))
        {
            throw new LargerOnSmallerElementException("Cannot place a larger element on a smaller one.");
        }
        _elements.Push(element);
    }

    public T Pop()
    {
        if (IsTowerEmpty()) throw new EmptyTowerException("The tower is empty.");
        return _elements.Pop();
    }

    private bool IsMoveValid(T element) => !IsTowerEmpty() && _elements.Peek().CompareTo(element) <= 0;
    private bool IsTowerEmpty() => _elements.Count == 0;

    public void Print()
    {
        if (IsTowerEmpty())
        {
            Console.Write("[  ]");
        }
        else
        {
            foreach (var element in _elements.Reverse())
            {
                element.Print();
            }
        }
    }

    public int GetElementsCount() => _elements.Count;
}
