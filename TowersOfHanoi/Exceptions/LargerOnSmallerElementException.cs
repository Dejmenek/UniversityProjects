namespace TowersOfHanoi.Exceptions;
public class LargerOnSmallerElementException : Exception
{
    public LargerOnSmallerElementException() { }

    public LargerOnSmallerElementException(string message) : base(message) { }

    public LargerOnSmallerElementException(string message, Exception inner) : base(message, inner) { }
}
